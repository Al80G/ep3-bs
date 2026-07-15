<?php

namespace Championship\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BracketServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new BracketService(
            $sm->get('Championship\Manager\GroupManager'),
            $sm->get('Championship\Manager\FixtureManager'),
            $sm->get('Championship\Service\StandingsService'));
    }

}
