<?php

namespace Championship\Manager;

use Base\Manager\AbstractManager;
use Championship\Entity\Category;
use Championship\Entity\Fixture;
use Championship\Entity\FixtureFactory;
use Championship\Entity\Group;
use Championship\Manager\Fixture\SetScoreManager;
use Championship\Table\FixtureTable;
use DateTime;
use InvalidArgumentException;
use RuntimeException;
use Zend\Db\Sql\Where;

class FixtureManager extends AbstractManager
{

    protected $matchTable;
    protected $setScoreManager;

    /**
     * Creates a new championship match manager object.
     *
     * @param FixtureTable $matchTable
     * @param SetScoreManager $setScoreManager
     */
    public function __construct(FixtureTable $matchTable, SetScoreManager $setScoreManager)
    {
        $this->matchTable = $matchTable;
        $this->setScoreManager = $setScoreManager;
    }

    /**
     * Creates a group stage match between two (or, for a bye, one) participants.
     *
     * @param int|Category $category
     * @param int|Group $group
     * @param int $participant1Pid
     * @param int $participant2Pid
     * @param string $roundName
     * @return Fixture
     * @throws InvalidArgumentException
     */
    public function createGroupMatch($category, $group, $participant1Pid, $participant2Pid = null, $roundName = 'Group stage')
    {
        $match = new Fixture(array(
            'catid' => $category instanceof Category ? $category->need('catid') : $category,
            'gid' => $group instanceof Group ? $group->need('gid') : $group,
            'round_type' => 'group',
            'round_name' => $roundName,
            'round_number' => 1,
            'participant1_pid' => $participant1Pid,
            'participant2_pid' => $participant2Pid,
            'status' => 'pending',
        ));

        $this->save($match);

        return $match;
    }

    /**
     * Generates the round-robin group stage matches for a group (every member plays every other member once).
     *
     * @param int|Category $category
     * @param int|Group $group
     * @param array $participantPids
     * @return array
     */
    public function generateGroupMatches($category, $group, array $participantPids)
    {
        $matches = array();

        $count = count($participantPids);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $matches[] = $this->createGroupMatch($category, $group, $participantPids[$i], $participantPids[$j]);
            }
        }

        return $matches;
    }

    /**
     * Regenerates the round-robin group stage matches for a group, replacing any existing
     * (not yet played) matches with a fresh set for the passed (current) group members.
     *
     * @param int|Category $category
     * @param Group $group
     * @param array $participantPids
     * @return array
     * @throws RuntimeException
     */
    public function regenerateGroupMatches($category, Group $group, array $participantPids)
    {
        $existingMatches = $this->getByGroup($group);

        foreach ($existingMatches as $match) {
            if ($match->isPlayed()) {
                throw new RuntimeException('Group matches cannot be regenerated once matches have been played');
            }
        }

        foreach ($existingMatches as $match) {
            $this->delete($match);
        }

        return $this->generateGroupMatches($category, $group, $participantPids);
    }

    /**
     * Creates a knock-out stage match. Either participant may be left empty (still waiting for
     * a previous round's winner); $nextMatchId/$nextMatchSlot link the winner into the following round.
     *
     * @param int|Category $category
     * @param string $roundName
     * @param int $roundNumber
     * @param int $participant1Pid
     * @param int $participant2Pid
     * @param int $nextMatchId
     * @param int $nextMatchSlot
     * @return Fixture
     */
    public function createKoMatch($category, $roundName, $roundNumber, $participant1Pid = null, $participant2Pid = null,
        $nextMatchId = null, $nextMatchSlot = null)
    {
        $match = new Fixture(array(
            'catid' => $category instanceof Category ? $category->need('catid') : $category,
            'gid' => null,
            'round_type' => 'ko',
            'round_name' => $roundName,
            'round_number' => $roundNumber,
            'participant1_pid' => $participant1Pid,
            'participant2_pid' => $participant2Pid,
            'next_match_id' => $nextMatchId,
            'next_match_slot' => $nextMatchSlot,
            'status' => 'pending',
        ));

        $this->save($match);

        return $match;
    }

    /**
     * Saves (updates or creates) a match.
     *
     * @param Fixture $match
     * @return Fixture
     * @throws RuntimeException
     */
    public function save(Fixture $match)
    {
        if ($match->get('mid')) {

            /* Update existing match */

            $updates = array();

            foreach ($match->need('updatedProperties') as $property) {
                $updates[$property] = $match->get($property);
            }

            if ($updates) {
                $this->matchTable->update($updates, array('mid' => $match->get('mid')));
            }

            $match->reset();

            $this->getEventManager()->trigger('save.update', $match);

        } else {

            /* Insert match */

            if (! $match->get('created')) {
                $match->add('created', (new DateTime())->format('Y-m-d H:i:s'));
            }

            $this->matchTable->insert(array(
                'catid' => $match->need('catid'),
                'gid' => $match->get('gid'),
                'round_type' => $match->need('round_type'),
                'round_name' => $match->need('round_name'),
                'round_number' => $match->get('round_number', 1),
                'participant1_pid' => $match->get('participant1_pid'),
                'participant2_pid' => $match->get('participant2_pid'),
                'next_match_id' => $match->get('next_match_id'),
                'next_match_slot' => $match->get('next_match_slot'),
                'status' => $match->get('status', 'pending'),
                'winner_pid' => $match->get('winner_pid'),
                'entered_by_uid' => $match->get('entered_by_uid'),
                'entered_at' => $match->get('entered_at'),
                'created' => $match->get('created'),
            ));

            $mid = $this->matchTable->getLastInsertValue();

            if (! (is_numeric($mid) && $mid > 0)) {
                throw new RuntimeException('Failed to save match');
            }

            $match->add('mid', $mid);

            $this->getEventManager()->trigger('save.insert', $match);
        }

        $this->getEventManager()->trigger('save', $match);

        return $match;
    }

    /**
     * Gets the match by primary id.
     *
     * @param int $mid
     * @param boolean $strict
     * @return Fixture
     * @throws RuntimeException
     */
    public function get($mid, $strict = true)
    {
        $match = $this->getBy(array('mid' => $mid));

        if (empty($match)) {
            if ($strict) {
                throw new RuntimeException('This match does not exist');
            }

            return null;
        } else {
            return current($match);
        }
    }

    /**
     * Gets all matches that match the passed conditions.
     *
     * @param mixed $where
     * @param string $order
     * @return array
     */
    public function getBy($where, $order = 'round_number ASC, mid ASC')
    {
        $select = $this->matchTable->getSql()->select();

        if ($where) {
            $select->where($where);
        }

        if ($order) {
            $select->order($order);
        }

        $resultSet = $this->matchTable->selectWith($select);

        return FixtureFactory::fromResultSet($resultSet);
    }

    /**
     * Gets all matches of the passed category.
     *
     * @param int|Category $category
     * @param string $order
     * @return array
     * @throws InvalidArgumentException
     */
    public function getByCategory($category, $order = 'round_number ASC, mid ASC')
    {
        if ($category instanceof Category) {
            $catid = $category->need('catid');
        } else {
            $catid = $category;
        }

        if (! (is_numeric($catid) && $catid > 0)) {
            throw new InvalidArgumentException('Category id must be numeric');
        }

        return $this->getBy(array('catid' => $catid), $order);
    }

    /**
     * Gets all group stage matches of the passed group.
     *
     * @param int|Group $group
     * @param string $order
     * @return array
     * @throws InvalidArgumentException
     */
    public function getByGroup($group, $order = 'mid ASC')
    {
        if ($group instanceof Group) {
            $gid = $group->need('gid');
        } else {
            $gid = $group;
        }

        if (! (is_numeric($gid) && $gid > 0)) {
            throw new InvalidArgumentException('Group id must be numeric');
        }

        return $this->getBy(array('gid' => $gid), $order);
    }

    /**
     * Gets all matches of the passed category the passed participant is involved in.
     *
     * @param int|Category $category
     * @param int $pid
     * @param string $order
     * @return array
     * @throws InvalidArgumentException
     */
    public function getByParticipant($category, $pid, $order = 'round_number ASC, mid ASC')
    {
        if ($category instanceof Category) {
            $catid = $category->need('catid');
        } else {
            $catid = $category;
        }

        if (! (is_numeric($catid) && $catid > 0)) {
            throw new InvalidArgumentException('Category id must be numeric');
        }

        $where = new Where();
        $where->equalTo('catid', $catid);

        $where->and;

        $nested = $where->nest();
        $nested->equalTo('participant1_pid', $pid);
        $nested->or;
        $nested->equalTo('participant2_pid', $pid);
        $nested->unnest();

        return $this->getBy($where, $order);
    }

    /**
     * Gets the set scores of a match.
     *
     * @param Fixture $match
     * @return array
     */
    public function getSets(Fixture $match)
    {
        return $this->setScoreManager->getByMatch($match);
    }

    /**
     * Records the (set-by-set) result of a match, computes the winner from the sets and,
     * if this match feeds into a following knock-out round, advances the winner into it.
     *
     * @param Fixture $match
     * @param array $sets            Array of ['score_participant1' => int, 'score_participant2' => int, ...]
     * @param int $enteredByUid
     * @return Fixture
     * @throws InvalidArgumentException
     */
    public function recordResult(Fixture $match, array $sets, $enteredByUid)
    {
        if (! $match->isReady()) {
            throw new InvalidArgumentException('This match has no two participants assigned yet');
        }

        if (count($sets) < 2) {
            throw new InvalidArgumentException('At least two sets are required to determine a winner');
        }

        $this->setScoreManager->replaceForMatch($match, $sets);

        $setsWon1 = 0;
        $setsWon2 = 0;

        foreach ($sets as $set) {
            if ($set['score_participant1'] > $set['score_participant2']) {
                $setsWon1++;
            } else {
                $setsWon2++;
            }
        }

        if ($setsWon1 == $setsWon2) {
            throw new InvalidArgumentException('The entered sets do not produce a winner');
        }

        $winnerPid = $setsWon1 > $setsWon2 ? $match->need('participant1_pid') : $match->need('participant2_pid');

        return $this->finishMatch($match, $winnerPid, $enteredByUid, 'played');
    }

    /**
     * Records a walkover (no sets played) for a match.
     *
     * @param Fixture $match
     * @param int $winnerPid
     * @param int $enteredByUid
     * @return Fixture
     * @throws InvalidArgumentException
     */
    public function recordWalkover(Fixture $match, $winnerPid, $enteredByUid)
    {
        if (! in_array($winnerPid, array($match->get('participant1_pid'), $match->get('participant2_pid')))) {
            throw new InvalidArgumentException('The winner must be one of this match\'s participants');
        }

        $this->setScoreManager->deleteByMatch($match);

        return $this->finishMatch($match, $winnerPid, $enteredByUid, 'walkover');
    }

    /**
     * Marks a match as finished with the passed winner and, if applicable, advances the
     * winner into the next linked match.
     *
     * @param Fixture $match
     * @param int $winnerPid
     * @param int $enteredByUid
     * @param string $status
     * @return Fixture
     */
    protected function finishMatch(Fixture $match, $winnerPid, $enteredByUid, $status)
    {
        $match->set('status', $status);
        $match->set('winner_pid', $winnerPid);
        $match->set('entered_by_uid', $enteredByUid);
        $match->set('entered_at', (new DateTime())->format('Y-m-d H:i:s'));

        $this->save($match);

        if ($match->get('next_match_id')) {
            $nextMatch = $this->get($match->need('next_match_id'));

            $slotProperty = ($match->need('next_match_slot') == 2) ? 'participant2_pid' : 'participant1_pid';

            $nextMatch->set($slotProperty, $winnerPid);

            $this->save($nextMatch);
        }

        return $match;
    }

    /**
     * Deletes one match.
     *
     * @param int|Fixture $match
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($match)
    {
        if ($match instanceof Fixture) {
            $mid = $match->need('mid');
        } else {
            $mid = $match;
        }

        if (! (is_numeric($mid) && $mid > 0)) {
            throw new InvalidArgumentException('Match id must be numeric');
        }

        $match = $this->get($mid);

        $this->setScoreManager->deleteByMatch($mid);

        $deletion = $this->matchTable->delete(array('mid' => $mid));

        $this->getEventManager()->trigger('delete', $match);

        return $deletion;
    }

}
