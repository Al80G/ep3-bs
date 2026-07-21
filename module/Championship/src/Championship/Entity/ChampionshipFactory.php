<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntityFactory;

class ChampionshipFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Championship\Entity\Championship';
    protected static $entityPrimary = 'cid';

}
