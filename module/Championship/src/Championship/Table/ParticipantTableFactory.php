<?php

namespace Championship\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ParticipantTableFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new ParticipantTable(ParticipantTable::NAME, $sm->get('Zend\Db\Adapter\Adapter'));
    }

}
