<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntityFactory;

class FixtureFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Championship\Entity\Fixture';
    protected static $entityPrimary = 'mid';

}
