<?php
declare(strict_types=1);

namespace Tests;

use App\Services\BookingService;
use App\ValidationException;

final class BookingServiceTest extends TestCase
{
    public function testCreatesPendingBookingWithCleanData(): void
    {
        $b = $this->app->booking->book($this->validBooking(), $this->now());
        $this->assertSame('pending', $b['status']);
        $this->assertSame('sarah@example.com', $b['email']);
        $this->assertSame('CH44 1AB', $b['postcode']);
        $this->assertSame('2026-01-07 09:00:00', $b['starts_at']);
        $this->assertSame('2026-01-07 10:00:00', $b['ends_at']);
        $this->assertMatchesRegularExpression('/^BK-[A-Z2-9]{6}$/', $b['reference']);
        $this->assertSame('Boiler service', $b['service_name']);
    }

    public function testRejectsDoubleBooking(): void
    {
        $this->app->booking->book($this->validBooking(), $this->now());
        try {
            $this->app->booking->book($this->validBooking(['customer_name' => 'Someone Else']), $this->now());
            $this->fail('Expected the second booking to be rejected');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('time', $e->errors);
        }
    }

    public function testRejectsTimesOutsideTheSlotGrid(): void
    {
        $this->expectException(ValidationException::class);
        $this->app->booking->book($this->validBooking(['time' => '09:10']), $this->now());
    }

    public function testRejectsImpossibleDates(): void
    {
        $this->expectException(ValidationException::class);
        $this->app->booking->book($this->validBooking(['date' => '2026-02-30']), $this->now());
    }

    public function testCollectsAllFieldErrors(): void
    {
        try {
            $this->app->booking->book([
                'service_id' => 999, 'customer_name' => '', 'email' => 'nope', 'phone' => '12',
                'address' => '', 'postcode' => 'XYZ', 'date' => '', 'time' => '',
            ], $this->now());
            $this->fail('Expected validation errors');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['service_id', 'customer_name', 'email', 'phone', 'address', 'postcode', 'time'],
                array_keys($e->errors)
            );
        }
    }

    public function testPostcodeNormalisation(): void
    {
        $this->assertSame('CH44 1AB', BookingService::normalisePostcode(' ch44  1ab '));
        $this->assertSame('L1 8JQ', BookingService::normalisePostcode('l18jq'));
        $this->assertSame('SW1A 1AA', BookingService::normalisePostcode('SW1A1AA'));
        $this->assertNull(BookingService::normalisePostcode('12345'));
    }

    public function testFindByReference(): void
    {
        $b = $this->app->booking->book($this->validBooking(), $this->now());
        $this->assertSame($b['id'], $this->app->bookings->findByReference($b['reference'])['id']);
        $this->assertNull($this->app->bookings->findByReference('BK-NOPE00'));
    }
}
