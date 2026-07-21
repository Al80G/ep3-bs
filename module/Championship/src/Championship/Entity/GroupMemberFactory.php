<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntityFactory;

class GroupMemberFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Championship\Entity\GroupMember';
    protected static $entityPrimary = 'gmid';

}
