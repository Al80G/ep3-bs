<?php

namespace Championship\Manager;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ParticipantManagerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new ParticipantManager($sm->get('Championship\Table\ParticipantTable'));
    }

}
