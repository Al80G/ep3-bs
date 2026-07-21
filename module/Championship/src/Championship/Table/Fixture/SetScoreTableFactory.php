<?php

namespace Championship\Table\Fixture;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SetScoreTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new SetScoreTable(SetScoreTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
