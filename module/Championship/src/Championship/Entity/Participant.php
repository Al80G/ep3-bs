<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntity;

class Participant extends AbstractEntity
{

    protected $pid;
    protected $catid;
    protected $uid;
    protected $partner_uid;
    protected $status;
    protected $seed;
    protected $created;

    /**
     * The possible status values.
     *
     * @var array
     */
    public static $statusOptions = array(
        'registered' => 'Registered',
        'withdrawn' => 'Withdrawn',
    );

    /**
     * Whether the passed user id belongs to this participant (single player or one half of a pair).
     *
     * @param int $uid
     * @return boolean
     */
    public function hasPlayer($uid)
    {
        return ($this->get('uid') == $uid) || ($this->get('partner_uid') && $this->get('partner_uid') == $uid);
    }

}
