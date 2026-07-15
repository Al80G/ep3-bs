<?php

namespace Championship\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GroupMemberTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new GroupMemberTable(GroupMemberTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
