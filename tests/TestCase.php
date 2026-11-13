<?php
declare(strict_types=1);

namespace Tests;

use App\App;
use App\Database;
use DateTimeImmutable;

/** Every test gets a fresh in-memory SQLite database with the real schema. */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected App $app;
    protected int $serviceId;   // 60-minute service

    protected function setUp(): void
    {
        $config = require dirname(__DIR__) . '/config.php';
        $config['booking'] = [
            'opening_hours' => [1 => ['09:00', '12:00'], 2 => ['09:00', '12:00'], 3 => ['09:00', '12:00'], 4 => ['09:00', '12:00'], 5 => ['09:00', '12:00']],
            'slot_minutes' => 30,
            'min_notice_hours' => 24,
            'max_days_ahead' => 30,
            'engineers' => 1,
        ];
        $db = Database::connect('sqlite::memory:');
        Database::migrate($db);
        $this->app = new App($db, $config);
        $this->serviceId = $this->app->services->create('Boiler service', 60, 8500);
    }

    /** Monday 5 January 2026, 10:00 */
    protected function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-05 10:00:00');
    }

    protected function validBooking(array $overrides = []): array
    {
        return $overrides + [
            'service_id' => $this->serviceId,
            'customer_name' => 'Sarah Jones',
            'email' => 'Sarah@Example.com',
            'phone' => '07700 900123',
            'address' => '12 Victoria Road',
            'postcode' => 'ch441ab',
            'date' => '2026-01-07',
            'time' => '09:00',
            'notes' => '',
        ];
    }
}
