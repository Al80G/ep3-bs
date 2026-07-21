<?php

namespace Championship\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GroupManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new GroupManager(
            $sm->get('Championship\Table\GroupTable'),
            $sm->get('Championship\Table\GroupMemberTable'));
    }

}
