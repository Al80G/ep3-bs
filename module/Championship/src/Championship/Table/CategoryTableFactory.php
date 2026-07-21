<?php

namespace Championship\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class CategoryTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new CategoryTable(CategoryTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
