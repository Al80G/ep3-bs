<?php

namespace Calendar\Controller\Plugin;

use DateTime;
use Exception;
use RuntimeException;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class DetermineDate extends AbstractPlugin
{

    public function __invoke()
    {
        $controller = $this->getController();

        try {
            $passedDate = $controller->params()->fromQuery('date');

            if (! $passedDate) {
                $passedDate = 'now';
            }

            $dateStart = new DateTime($passedDate);
            $dateStart->setTime(0, 0);

            return $dateStart;
        } catch (Exception $e) {
            throw new RuntimeException('The passed calendar date is invalid');
        }
    }

}