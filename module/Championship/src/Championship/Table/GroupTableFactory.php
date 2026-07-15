<?php

namespace Championship\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GroupTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new GroupTable(GroupTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
