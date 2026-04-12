<?php

namespace Calendar\View\Helper\Cell\Render;

use Square\Entity\Square;
use Zend\View\Helper\AbstractHelper;

class Free extends AbstractHelper
{

    public function __invoke($user, $userBooking, array $reservations, array $cellLinkParams, Square $square)
    {
        $view = $this->getView();

	    $labelFree = $square->getMeta('label.free', $this->view->t('Free'));

        if ($user && $user->can('calendar.see-data, calendar.create-single-bookings')) {
            return $view->calendarCellRenderFreeForPrivileged($reservations, $cellLinkParams, $square);
        } else if ($user) {
            if ($userBooking) {

                $cellLabel = $view->t('Your Booking');
                $cellGroup = ' cc-group-' . $userBooking->need('bid');

                if ($userBooking->getMeta('directpay') == 'true' and $userBooking->get('status_billing')!= 'paid') {
                    $cellLabel = $view->t('Your Booking TRY');
                }

                return $view->calendarCellLink($cellLabel, $view->url('square', [], $cellLinkParams), 'cc-own' . $cellGroup);
            } else {
                return $view->calendarCellLink($labelFree, $view->url('square', [], $cellLinkParams), 'cc-free');
            }
        } else {
            return $view->calendarCellLink($labelFree, $view->url('square', [], $cellLinkParams), 'cc-free');
        }
    }

}
