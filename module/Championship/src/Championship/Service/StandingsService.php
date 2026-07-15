<?php

namespace Championship\Service;

use Championship\Entity\Group;
use Championship\Manager\FixtureManager;

class StandingsService
{

    protected $matchManager;

    /**
     * Creates a new championship standings service object.
     *
     * @param FixtureManager $matchManager
     */
    public function __construct(FixtureManager $matchManager)
    {
        $this->matchManager = $matchManager;
    }

    /**
     * Computes the group table (wins, losses, sets, set difference) for the passed group,
     * ordered by wins (descending), then set difference (descending).
     *
     * Only matches with status "played" or "walkover" are counted; set counts are only
     * derived from matches with recorded set scores (i.e. not walkovers).
     *
     * @param Group $group
     * @return array               List of ['pid', 'played', 'wins', 'losses', 'sets_won', 'sets_lost', 'set_diff']
     */
    public function getStandings(Group $group)
    {
        $matches = $this->matchManager->getByGroup($group);

        $stats = array();

        foreach ($matches as $match) {
            foreach (array('participant1_pid', 'participant2_pid') as $slot) {
                $pid = $match->get($slot);

                if ($pid && ! isset($stats[$pid])) {
                    $stats[$pid] = array(
                        'pid' => $pid,
                        'played' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'sets_won' => 0,
                        'sets_lost' => 0,
                    );
                }
            }

            if (! ($match->isReady() && $match->isPlayed())) {
                continue;
            }

            $participant1Pid = $match->need('participant1_pid');
            $participant2Pid = $match->need('participant2_pid');
            $winnerPid = $match->need('winner_pid');
            $loserPid = ($winnerPid == $participant1Pid) ? $participant2Pid : $participant1Pid;

            $stats[$participant1Pid]['played']++;
            $stats[$participant2Pid]['played']++;
            $stats[$winnerPid]['wins']++;
            $stats[$loserPid]['losses']++;

            if ($match->get('status') == 'played') {
                foreach ($this->matchManager->getSets($match) as $set) {
                    if ($set->need('score_participant1') > $set->need('score_participant2')) {
                        $stats[$participant1Pid]['sets_won']++;
                        $stats[$participant2Pid]['sets_lost']++;
                    } else {
                        $stats[$participant2Pid]['sets_won']++;
                        $stats[$participant1Pid]['sets_lost']++;
                    }
                }
            }
        }

        foreach ($stats as &$row) {
            $row['set_diff'] = $row['sets_won'] - $row['sets_lost'];
        }

        unset($row);

        usort($stats, function ($a, $b) {
            if ($a['wins'] != $b['wins']) {
                return $b['wins'] <=> $a['wins'];
            }

            return $b['set_diff'] <=> $a['set_diff'];
        });

        return array_values($stats);
    }

}
