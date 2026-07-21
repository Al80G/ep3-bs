<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntity;

class Fixture extends AbstractEntity
{

    protected $mid;
    protected $catid;
    protected $gid;
    protected $round_type;
    protected $round_name;
    protected $round_number;
    protected $participant1_pid;
    protected $participant2_pid;
    protected $next_match_id;
    protected $next_match_slot;
    protected $status;
    protected $winner_pid;
    protected $entered_by_uid;
    protected $entered_at;
    protected $created;

    /**
     * The possible round type values.
     *
     * @var array
     */
    public static $roundTypeOptions = array(
        'group' => 'Group stage',
        'ko' => 'Knock-out stage',
    );

    /**
     * The possible status values.
     *
     * @var array
     */
    public static $statusOptions = array(
        'pending' => 'Pending',
        'scheduled' => 'Scheduled',
        'played' => 'Played',
        'walkover' => 'Walkover',
    );

    /**
     * Whether this match already has a result.
     *
     * @return boolean
     */
    public function isPlayed()
    {
        return in_array($this->get('status'), array('played', 'walkover'));
    }

    /**
     * Whether both participant slots are filled (i.e. it is not waiting for a bye/previous round).
     *
     * @return boolean
     */
    public function isReady()
    {
        return $this->get('participant1_pid') && $this->get('participant2_pid');
    }

}
