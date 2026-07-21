<?php

namespace Championship\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FixtureManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new FixtureManager(
            $sm->get('Championship\Table\FixtureTable'),
            $sm->get('Championship\Manager\Fixture\SetScoreManager'));
    }

}
