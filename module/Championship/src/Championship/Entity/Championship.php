<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntity;

class Championship extends AbstractEntity
{

    protected $cid;
    protected $name;
    protected $status;
    protected $datetime_registration_start;
    protected $datetime_registration_end;
    protected $created;

    /**
     * The possible status values.
     *
     * @var array
     */
    public static $statusOptions = array(
        'draft' => 'Draft',
        'open' => 'Open for registration',
        'running' => 'Running',
        'finished' => 'Finished',
    );

}
