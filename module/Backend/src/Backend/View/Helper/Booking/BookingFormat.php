<?php

namespace Backend\View\Helper\Booking;

use Booking\Entity\Reservation;
use Square\Manager\SquareManager;
use Booking\Manager\Booking\BillManager;
use User\Manager\UserManager;
use Zend\View\Helper\AbstractHelper;

class BookingFormat extends AbstractHelper
{

    protected $squareManager;
    protected $bookingBillManager;
    protected $userManager; 

    public function __construct(SquareManager $squareManager, BillManager $bookingBillManager, UserManager $userManager )
    {
        $this->squareManager = $squareManager;
        $this->bookingBillManager = $bookingBillManager;
        $this->userManager = $userManager;
    }

    public function __invoke(Reservation $reservation, $dateStart = null, $dateEnd = null, $search = null)
    {
        $view = $this->getView();
        $html = '';

        $booking = $reservation->needExtra('booking');

        switch ($booking->need('status')) {
            case 'cancelled':
                $attr = ' class="gray"';
                break;
            default:
                $attr = null;
                break;
        }

        $html .= sprintf('<tr %s>', $attr);

        $html .= sprintf('<td class="status-col right-text first-letter-only">%s</td>',
            $view->t($booking->getStatus()));

        $html .= sprintf('<td>%s</td>',
            $booking->need('bid'));

        if ($booking->getExtra('user')) {
            $userName = $booking->getExtra('user')->get('alias');
        } else {
            $userName = $booking->need('uid');
        }

        $user = $this->userManager->get($booking->need('uid'));

        $html .= sprintf('<td><b>%s</b></td>',
            $userName);

        $member = 0;
        if ($user != null && $user->getMeta('member') != null) {
            $member = $user->getMeta('member');
        }

        if ($member) {
        $html .= sprintf('<td class="member-col">%s</td>',
            $view->t('Yes'));
        }
        else {
        $html .= sprintf('<td class="member-col">%s</td>',
            $view->t('No'));
        }

        /* Date and time col */

        $date = new \DateTime($reservation->get('date'));

        $fullDate = $view->dateFormat($date, \IntlDateFormatter::FULL);
        $fullDateParts = explode(', ', $fullDate);

        $html .= sprintf('<td class="day-col">%s</td>',
            $fullDateParts[0]);

        $html .= sprintf('<td>%s</td>',
            $view->dateFormat($date, \IntlDateFormatter::MEDIUM));

        $html .= sprintf('<td>%s</td>',
            $view->timeRange($reservation->get('time_start'), $reservation->get('time_end'), '%s to %s'));

        /* Square col */

        if ($booking->get('sid')) {
            $squareName = $this->squareManager->get($booking->get('sid'))->get('name');
        } else {
            $squareName = '-';
        }

        $html .= sprintf('<td>%s</td>',
            $squareName);

        /* Notes col */

        $notes = $booking->getMeta('notes');

        if ($notes) {
            if (strlen($notes) > 48) {
                $notes = substr($notes, 0, 48) . '&hellip;';
            }

            $notes = '<span class="small-text">' . $notes . '</span>';
        } else {
            $notes = '-';
        }

        $html .= sprintf('<td class="notes-col">%s</td>',
            $notes);

        /* Price col */

        $price = 0;

        $bills = $this->bookingBillManager->getBy(array('bid' => $booking->need('bid')), 'bbid ASC'); 

        if ($bills) {
            foreach ($bills as $bill) {
               $price += $bill->need('price');
           }
        }

        $html .= sprintf('<td>%s</td>',
            $view->priceFormat($price));

        /* Actions col */

        if ($booking->get('status') == 'cancelled') {

            $html .= sprintf('<td class="actions-col no-print"><a href="%s" class="unlined gray symbolic symbolic-edit"><span class="symbolic-label">%s</span></a></td>',
                $view->url('backend/booking/edit', [], ['query' => [
                    'ds' => $date->format('Y-m-d'),
                    'ts' => substr($reservation->get('time_start'), 0, 5),
                    'te' => substr($reservation->get('time_end'), 0, 5),
                    's' => $booking->get('sid'),
                    'r' => $reservation->get('rid')]]),
                $view->t('Edit'));

        } else {

            $html .= sprintf('<td class="actions-col no-print"><a href="%s" class="unlined gray symbolic symbolic-edit"><span class="symbolic-label">%s</span></a></td>',
                $view->url('backend/booking/edit', [], ['query' => [
                    'ds' => $date->format('Y-m-d'),
                    'ts' => substr($reservation->get('time_start'), 0, 5),
                    'te' => substr($reservation->get('time_end'), 0, 5),
                    's' => $booking->get('sid'),
                    'r' => $reservation->get('rid')]]),
                $view->t('Edit'));
        }

        $html .= '</tr>';

        return $html;
    }

}
