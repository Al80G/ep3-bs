<?php

namespace Championship\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class StandingsServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new StandingsService($sm->get('Championship\Manager\FixtureManager'));
    }

}
