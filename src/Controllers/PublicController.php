<?php
declare(strict_types=1);

namespace App\Controllers;

use App\App;
use App\Csrf;
use App\ValidationException;
use App\View;
use DateTimeImmutable;

final class PublicController
{
    public function __construct(private readonly App $app, private readonly View $view)
    {
    }

    public function bookingForm(array $errors = [], array $old = []): string
    {
        $now = new DateTimeImmutable();
        return $this->view->render('public/book', [
            'title'    => 'Book a visit',
            'services' => $this->app->services->active(),
            'days'     => $this->app->availability->openDays($now),
            'errors'   => $errors,
            'old'      => $old,
            'csrf'     => Csrf::token($_SESSION),
        ]);
    }

    /** JSON list of free start times, used by the booking form when a date or service changes. */
    public function slots(): string
    {
        header('Content-Type: application/json');
        $service = $this->app->services->find((int) ($_GET['service_id'] ?? 0));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($_GET['date'] ?? ''));
        if ($service === null || $date === false) {
            http_response_code(422);
            return json_encode(['error' => 'Choose a service and a date']);
        }
        $slots = $this->app->availability->slotsFor($date, (int) $service['duration_minutes'], new DateTimeImmutable());
        return json_encode(['slots' => array_map(static fn ($s) => $s->format('H:i'), $slots)]);
    }

    public function submitBooking(): string
    {
        if (!Csrf::valid($_SESSION, $_POST['_csrf'] ?? null)) {
            http_response_code(419);
            return $this->bookingForm(['form' => 'Your session expired. Please try again.'], $_POST);
        }
        try {
            $booking = $this->app->booking->book($_POST, new DateTimeImmutable());
        } catch (ValidationException $e) {
            http_response_code(422);
            return $this->bookingForm($e->errors, $_POST);
        }
        // Post/Redirect/Get, so refreshing the page can't create a duplicate booking.
        header('Location: /booked?ref=' . urlencode($booking['reference']), true, 303);
        return '';
    }

    public function booked(): string
    {
        $booking = $this->app->bookings->findByReference((string) ($_GET['ref'] ?? ''));
        if ($booking === null) {
            http_response_code(404);
            return $this->view->render('public/not_found', ['title' => 'Not found']);
        }
        return $this->view->render('public/booked', ['title' => 'Booking received', 'booking' => $booking]);
    }
}
