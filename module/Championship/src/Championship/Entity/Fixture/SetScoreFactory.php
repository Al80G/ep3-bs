<?php

namespace Championship\Entity\Fixture;

use Base\Entity\AbstractEntityFactory;

class SetScoreFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Championship\Entity\Fixture\SetScore';
    protected static $entityPrimary = 'msid';

}
