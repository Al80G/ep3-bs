<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntityFactory;

class GroupFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Championship\Entity\Group';
    protected static $entityPrimary = 'gid';

}
