<?php

namespace Championship\Service;

use Championship\Entity\Category;
use Championship\Entity\Fixture;
use Championship\Manager\GroupManager;
use Championship\Manager\FixtureManager;
use RuntimeException;

/**
 * Generates the knock-out stage (quarterfinal/semifinal/final) bracket for a category from its
 * group stage standings, seeding qualifiers so that group-mates meet as late as possible.
 *
 * The generated bracket is only a starting point: every match it creates is a plain, freely
 * editable Championship\Entity\Fixture row (as long as it has not been played yet), so an admin
 * can always override any pairing afterwards.
 */
class BracketService
{

    /**
     * The supported bracket sizes and their round names.
     *
     * @var array
     */
    protected static $roundNames = array(
        8 => 'Quarterfinal',
        4 => 'Semifinal',
        2 => 'Final',
    );

    protected $groupManager;
    protected $matchManager;
    protected $standingsService;

    /**
     * Creates a new championship bracket service object.
     *
     * @param GroupManager $groupManager
     * @param FixtureManager $matchManager
     * @param StandingsService $standingsService
     */
    public function __construct(GroupManager $groupManager, FixtureManager $matchManager, StandingsService $standingsService)
    {
        $this->groupManager = $groupManager;
        $this->matchManager = $matchManager;
        $this->standingsService = $standingsService;
    }

    /**
     * Whether a knock-out stage has already been generated for this category.
     *
     * @param Category $category
     * @return boolean
     */
    public function isGenerated(Category $category)
    {
        return (bool) $this->getKoMatches($category);
    }

    /**
     * Generates the knock-out stage for a category from the current group standings.
     *
     * @param Category $category
     * @param boolean $regenerate       If true, any previously generated (and not yet played) knock-out
     *                                  stage is deleted first. Throws otherwise if one already exists.
     * @return array                   The first-round matches of the newly created knock-out stage.
     * @throws RuntimeException
     */
    public function generate(Category $category, $regenerate = false)
    {
        $existingKoMatches = $this->getKoMatches($category);

        if ($existingKoMatches) {
            if (! $regenerate) {
                throw new RuntimeException('The knock-out stage has already been generated for this category');
            }

            foreach ($existingKoMatches as $match) {
                if ($match->isPlayed()) {
                    throw new RuntimeException('The knock-out stage cannot be regenerated once matches have been played');
                }
            }

            foreach ($existingKoMatches as $match) {
                $this->matchManager->delete($match);
            }
        }

        $qualifiers = $this->getQualifiers($category);

        $count = count($qualifiers);

        if ($count < 2) {
            throw new RuntimeException('Not enough qualified participants to create a knock-out stage');
        }

        if ($count > 8) {
            throw new RuntimeException('More than 8 qualified participants are not supported; reduce "advance per group" for this category');
        }

        $bracketSize = 2;

        while ($bracketSize < $count) {
            $bracketSize *= 2;
        }

        $slots = array_fill(0, $bracketSize, null);

        foreach ($this->seedPositions($bracketSize) as $slotIndex => $seedIndex) {
            $slots[$slotIndex] = $qualifiers[$seedIndex] ?? null;
        }

        $roundMatches = array();

        for ($i = 0; $i < $bracketSize; $i += 2) {
            $roundMatches[] = $this->matchManager->createKoMatch(
                $category, static::$roundNames[$bracketSize], 1, $slots[$i], $slots[$i + 1]);
        }

        foreach ($roundMatches as $match) {
            $this->autoAdvanceBye($match);
        }

        $playersThisRound = $bracketSize / 2;
        $roundNumber = 2;

        while ($playersThisRound >= 2) {
            $nextRoundMatches = array();

            for ($i = 0; $i < count($roundMatches); $i += 2) {
                $matchA = $roundMatches[$i];
                $matchB = $roundMatches[$i + 1];

                $nextMatch = $this->matchManager->createKoMatch($category, static::$roundNames[$playersThisRound], $roundNumber);

                $this->linkToNextMatch($matchA, $nextMatch, 1);
                $this->linkToNextMatch($matchB, $nextMatch, 2);

                $nextRoundMatches[] = $nextMatch;
            }

            $roundMatches = $nextRoundMatches;
            $playersThisRound = $playersThisRound / 2;
            $roundNumber++;
        }

        return $this->matchManager->getByCategory($category);
    }

    /**
     * Gets the qualified participant ids for the knock-out stage, ordered by seed
     * (all group winners first, then all runners-up, etc.), derived from the current
     * group standings and each category's "advance per group" setting.
     *
     * @param Category $category
     * @return array
     */
    protected function getQualifiers(Category $category)
    {
        $groups = $this->groupManager->getByCategory($category);

        if (empty($groups)) {
            throw new RuntimeException('No groups defined for this category');
        }

        $advancePerGroup = (int) $category->get('advance_per_group', 2);

        $qualifiersByGroup = array();

        foreach ($groups as $group) {
            $standings = $this->standingsService->getStandings($group);
            $qualifiersByGroup[] = array_slice(array_column($standings, 'pid'), 0, $advancePerGroup);
        }

        $qualifiers = array();

        for ($rank = 0; $rank < $advancePerGroup; $rank++) {
            foreach ($qualifiersByGroup as $pids) {
                if (isset($pids[$rank])) {
                    $qualifiers[] = $pids[$rank];
                }
            }
        }

        return $qualifiers;
    }

    /**
     * Gets all knock-out stage matches of a category.
     *
     * @param Category $category
     * @return array
     */
    protected function getKoMatches(Category $category)
    {
        return $this->matchManager->getBy(array('catid' => $category->need('catid'), 'round_type' => 'ko'));
    }

    /**
     * If exactly one of the match's two participant slots is filled (a bye), immediately
     * records a walkover so the present participant advances without having to play.
     *
     * @param Fixture $match
     */
    protected function autoAdvanceBye(Fixture $match)
    {
        $participant1Pid = $match->get('participant1_pid');
        $participant2Pid = $match->get('participant2_pid');

        if ($participant1Pid && ! $participant2Pid) {
            $this->matchManager->recordWalkover($match, $participant1Pid, null);
        } else if ($participant2Pid && ! $participant1Pid) {
            $this->matchManager->recordWalkover($match, $participant2Pid, null);
        }
    }

    /**
     * Links a match to the match of the following round it feeds into and, if it has already
     * been decided (i.e. it was a bye), immediately advances its winner into that next match.
     *
     * @param Fixture $match
     * @param Fixture $nextMatch
     * @param int $slot
     */
    protected function linkToNextMatch(Fixture $match, Fixture $nextMatch, $slot)
    {
        $match->set('next_match_id', $nextMatch->need('mid'));
        $match->set('next_match_slot', $slot);

        $this->matchManager->save($match);

        if ($match->isPlayed()) {
            $nextMatch->set($slot == 2 ? 'participant2_pid' : 'participant1_pid', $match->need('winner_pid'));
            $this->matchManager->save($nextMatch);
        }
    }

    /**
     * Computes the standard tournament bracket seeding order for the passed bracket size,
     * i.e. which seed (0-indexed) each bracket slot (0-indexed) is assigned to, so that the
     * top two seeds can only meet in the final, seeds 1-4 only from the semifinal on, etc.
     *
     * @param int $bracketSize      Must be a power of two.
     * @return array                slotIndex => seedIndex
     */
    protected function seedPositions($bracketSize)
    {
        $seeds = array(1, 2);

        while (count($seeds) < $bracketSize) {
            $n = count($seeds);
            $nextSeeds = array();

            foreach ($seeds as $seed) {
                $nextSeeds[] = $seed;
                $nextSeeds[] = (2 * $n + 1) - $seed;
            }

            $seeds = $nextSeeds;
        }

        $positions = array();

        foreach ($seeds as $slotIndex => $seedNumber) {
            $positions[$slotIndex] = $seedNumber - 1;
        }

        return $positions;
    }

}
