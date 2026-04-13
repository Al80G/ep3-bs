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
                $isBallmaschine = $userBooking->getMeta('ballmaschine') == '1';
                $style = $isBallmaschine ? 'cc-own-ballmaschine' : 'cc-own';
                $cellStyle = $isBallmaschine ? 'background-color: #5889B8; color: #FFF; opacity: 1' : null;

                 if ($userBooking->getMeta('directpay') == 'true' and $userBooking->get('status_billing')!= 'paid') {
                     $cellLabel = $view->t('Your Booking TRY');
                     $style = 'cc-try';
                     $cellStyle = null;
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
                        $isBallmaschine = $booking->getMeta('ballmaschine') == '1';
                        $singleClass = $isBallmaschine ? 'cc-single-ballmaschine' : 'cc-single';
                        $cellStyle = $isBallmaschine ? 'background-color: #6C9CC4; color: #FFF; opacity: 1' : null;

                        switch ($booking->need('status')) {
                            case 'single':
                                return $view->calendarCellLink($bookerLabel . $playerSuffix, $view->url('square', [], $cellLinkParams), $singleClass . $cellGroup, null, $cellStyle);
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
