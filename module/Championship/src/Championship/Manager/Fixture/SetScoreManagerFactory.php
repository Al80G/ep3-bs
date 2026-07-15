<?php

namespace Championship\Manager\Fixture;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SetScoreManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new SetScoreManager($sm->get('Championship\Table\Fixture\SetScoreTable'));
    }

}
