<?php

namespace Championship\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ChampionshipTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new ChampionshipTable(ChampionshipTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
