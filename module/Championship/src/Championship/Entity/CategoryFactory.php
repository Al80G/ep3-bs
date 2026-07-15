<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntityFactory;

class CategoryFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Championship\Entity\Category';
    protected static $entityPrimary = 'catid';

}
