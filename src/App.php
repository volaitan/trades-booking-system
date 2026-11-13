<?php
declare(strict_types=1);

namespace App;

use App\Repositories\BookingRepository;
use App\Repositories\QuoteRepository;
use App\Repositories\ServiceRepository;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\InvoiceService;
use PDO;

/** Builds every service once from config. A tiny hand-written container. */
final class App
{
    public readonly BookingRepository $bookings;
    public readonly ServiceRepository $services;
    public readonly QuoteRepository $quotes;
    public readonly AvailabilityService $availability;
    public readonly BookingService $booking;
    public readonly InvoiceService $invoices;
    public readonly Auth $auth;

    /** @param array<string, mixed> $config */
    public function __construct(public readonly PDO $db, public readonly array $config)
    {
        $this->bookings = new BookingRepository($db);
        $this->services = new ServiceRepository($db);
        $this->quotes = new QuoteRepository($db);
        $this->availability = new AvailabilityService($this->bookings, $config['booking']);
        $this->booking = new BookingService($db, $this->bookings, $this->services, $this->availability);
        $this->invoices = new InvoiceService($db, $this->quotes, (int) $config['invoices']['payment_terms_days']);
        $this->auth = new Auth($db);
    }

    /** @param array<string, mixed> $config */
    public static function fromConfig(array $config): self
    {
        $db = Database::connect($config['database']['dsn'], $config['database']['user'], $config['database']['password']);
        return new self($db, $config);
    }
}
