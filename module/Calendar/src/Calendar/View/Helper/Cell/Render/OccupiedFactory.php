<?php

namespace Calendar\View\Helper\Cell\Render;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class OccupiedFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        $userManager = $sm->getServiceLocator()->get('User\Manager\UserManager');
        return new Occupied($userManager);
    }

}
