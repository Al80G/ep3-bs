<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntity;

class Category extends AbstractEntity
{

    protected $catid;
    protected $cid;
    protected $discipline;
    protected $gender;
    protected $name;
    protected $group_size_max;
    protected $advance_per_group;
    protected $admin_assigns_partners;
    protected $status;

    /**
     * The possible discipline values.
     *
     * @var array
     */
    public static $disciplineOptions = array(
        'single' => 'Single',
        'double' => 'Double',
    );

    /**
     * The possible gender values.
     *
     * @var array
     */
    public static $genderOptions = array(
        'men' => 'Men',
        'women' => 'Women',
        'mixed' => 'Mixed',
    );

    /**
     * The possible status values.
     *
     * @var array
     */
    public static $statusOptions = array(
        'disabled' => 'Disabled',
        'enabled' => 'Enabled',
    );

}
