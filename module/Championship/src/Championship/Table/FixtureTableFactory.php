<?php

namespace Championship\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FixtureTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new FixtureTable(FixtureTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
