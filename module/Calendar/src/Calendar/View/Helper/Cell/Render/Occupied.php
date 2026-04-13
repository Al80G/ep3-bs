<?php

namespace Calendar\View\Helper\Cell\Render;

use Square\Entity\Square;
use Zend\View\Helper\AbstractHelper;

class Occupied extends AbstractHelper
{

    public function __invoke($user, $userBooking, array $reservations, array $cellLinkParams, Square $square)
    {
        $view = $this->getView();

        $isSubscription = count($reservations) == 1 && current($reservations)->needExtra('booking')->need('status') == 'subscription';

        if ($user && ($user->need('status') == 'admin' || ($isSubscription && $user->can('calendar.create-subscription-bookings')))) {
            return $view->calendarCellRenderOccupiedForPrivileged($reservations, $cellLinkParams);
        } else if ($user) {
            if ($userBooking) {
                $cellLabel = $view->t('Your Booking');
                $cellGroup = ' cc-group-' . $userBooking->need('bid');
                $style = 'cc-own';
                $cellStyle = $userBooking->getMeta('ballmaschine') == '1' ? 'color: #FFFED6' : null;

                 if ($userBooking->getMeta('directpay') == 'true' and $userBooking->get('status_billing')!= 'paid') {
                     $cellLabel = $view->t('Your Booking TRY');
                     $style = 'cc-try';
                 }

                $playerSuffix = $this->getPlayerSuffix($userBooking, $view);
                if ($playerSuffix) {
                    $cellLabel .= $playerSuffix;
                }

                return $view->calendarCellLink($cellLabel, $view->url('square', [], $cellLinkParams), $style . $cellGroup, null, $cellStyle);
            } else {
                /* Other logged-in user: show names + players from meta */
                if (count($reservations) == 1) {
                    $reservation = current($reservations);
                    $booking = $reservation->needExtra('booking');
                    $playerSuffix = $this->getPlayerSuffix($booking, $view);

                    if ($playerSuffix) {
                        $bookerLabel = $view->escapeHtml($booking->needExtra('user')->need('alias'));
                        $cellGroup = ' cc-group-' . $booking->need('bid');
                        $cellStyle = $booking->getMeta('ballmaschine') == '1' ? 'color: #FFFED6' : null;

                        switch ($booking->need('status')) {
                            case 'single':
                                return $view->calendarCellLink($bookerLabel . $playerSuffix, $view->url('square', [], $cellLinkParams), 'cc-single' . $cellGroup, null, $cellStyle);
                            case 'subscription':
                                return $view->calendarCellLink($bookerLabel . $playerSuffix, $view->url('square', [], $cellLinkParams), 'cc-multiple' . $cellGroup);
                        }
                    }
                }

                return $view->calendarCellRenderOccupiedForVisitors($reservations, $cellLinkParams, $square, $user);
            }
        } else {
            return $view->calendarCellRenderOccupiedForVisitors($reservations, $cellLinkParams, $square);
        }
    }

    protected function getPlayerSuffix($booking, $view)
    {
        $raw = $booking->getMeta('player-names');
        if (!$raw) return '';

        $players = @unserialize($raw);
        if (!$players || !is_array($players)) return '';

        $names = [];
        foreach ($players as $entry) {
            $name = isset($entry['value']) ? trim($entry['value']) : '';
            if ($name !== '') {
                $names[] = $view->escapeHtml($name);
            }
        }

        if (empty($names)) return '';

        return '<br><span class="cc-players">+ ' . implode(', ', $names) . '</span>';
    }

}
