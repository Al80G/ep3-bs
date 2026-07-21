<?php

namespace Championship\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ChampionshipManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new ChampionshipManager($sm->get('Championship\Table\ChampionshipTable'));
    }

}
