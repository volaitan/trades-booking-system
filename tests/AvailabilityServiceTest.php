<?php
declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;

final class AvailabilityServiceTest extends TestCase
{
    private function times(string $day, int $minutes = 60): array
    {
        $slots = $this->app->availability->slotsFor(new DateTimeImmutable($day), $minutes, $this->now());
        return array_map(static fn ($s) => $s->format('H:i'), $slots);
    }

    public function testOffersSlotsThatFinishBeforeClosing(): void
    {
        // Open 09:00–12:00; a 60-minute job can start up to 11:00
        $this->assertSame(['09:00', '09:30', '10:00', '10:30', '11:00'], $this->times('2026-01-07'));
        $this->assertSame(['09:00', '09:30', '10:00', '10:30', '11:00', '11:30'], $this->times('2026-01-07', 30));
    }

    public function testClosedDaysHaveNoSlots(): void
    {
        $this->assertSame([], $this->times('2026-01-10')); // Saturday (closed in test config)
        $this->assertSame([], $this->times('2026-01-11')); // Sunday
    }

    public function testMinimumNoticeIsRespected(): void
    {
        // Now is Mon 10:00 with 24h notice: Tuesday only from 10:00
        $this->assertSame(['10:00', '10:30', '11:00'], $this->times('2026-01-06'));
        $this->assertSame([], $this->times('2026-01-05'));
    }

    public function testBookingWindowIsRespected(): void
    {
        $this->assertSame([], $this->times('2026-02-09')); // more than 30 days ahead
        $this->assertSame([], $this->times('2026-01-02')); // in the past
    }

    public function testExistingBookingsBlockOverlappingSlots(): void
    {
        $this->app->booking->book($this->validBooking(['time' => '10:00']), $this->now());
        // 10:00–11:00 is taken: a 60-min job can't start at 09:30, 10:00 or 10:30
        $this->assertSame(['09:00', '11:00'], $this->times('2026-01-07'));
    }

    public function testCancelledBookingsFreeTheSlot(): void
    {
        $booking = $this->app->booking->book($this->validBooking(['time' => '10:00']), $this->now());
        $this->app->bookings->setStatus((int) $booking['id'], 'cancelled');
        $this->assertContains('10:00', $this->times('2026-01-07'));
    }

    public function testTwoEngineersCanWorkAtOnce(): void
    {
        $config = $this->app->config;
        $config['booking']['engineers'] = 2;
        $app = new \App\App($this->app->db, $config);
        $app->booking->book($this->validBooking(['time' => '10:00']), $this->now());
        $slots = $app->availability->slotsFor(new DateTimeImmutable('2026-01-07'), 60, $this->now());
        $this->assertCount(5, $slots);
    }

    public function testOpenDaysSkipsClosedDays(): void
    {
        $days = $this->app->availability->openDays($this->now());
        foreach ($days as $d) {
            $this->assertLessThanOrEqual(5, (int) $d->format('N'));
        }
        $this->assertSame('2026-01-05', $days[0]->format('Y-m-d'));
    }
}
