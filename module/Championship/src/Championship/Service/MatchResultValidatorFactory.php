<?php

namespace Championship\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MatchResultValidatorFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new MatchResultValidator($sm->get('Championship\Manager\ParticipantManager'));
    }

}
