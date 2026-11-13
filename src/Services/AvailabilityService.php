<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\BookingRepository;
use DateInterval;
use DateTimeImmutable;

/**
 * Works out which start times can be offered for a service on a given day.
 *
 * A slot is available when it:
 *  - falls inside opening hours (and the whole job finishes before closing),
 *  - respects the minimum notice period and the booking window,
 *  - does not overlap more existing jobs than there are engineers.
 */
final class AvailabilityService
{
    /** @param array{opening_hours: array<int, array{0:string,1:string}>, slot_minutes:int, min_notice_hours:int, max_days_ahead:int, engineers:int} $rules */
    public function __construct(
        private readonly BookingRepository $bookings,
        private readonly array $rules,
    ) {
    }

    /** @return list<DateTimeImmutable> */
    public function slotsFor(DateTimeImmutable $day, int $durationMinutes, DateTimeImmutable $now): array
    {
        $day = $day->setTime(0, 0);
        $hours = $this->rules['opening_hours'][(int) $day->format('N')] ?? null;
        if ($hours === null || !$this->withinWindow($day, $now)) {
            return [];
        }

        [$open, $close] = [$this->at($day, $hours[0]), $this->at($day, $hours[1])];
        $earliest = $now->add(new DateInterval('PT' . $this->rules['min_notice_hours'] . 'H'));
        $duration = new DateInterval("PT{$durationMinutes}M");
        $step = new DateInterval('PT' . $this->rules['slot_minutes'] . 'M');
        $existing = $this->bookings->activeBetween($open, $close);

        $slots = [];
        for ($start = $open; $start->add($duration) <= $close; $start = $start->add($step)) {
            if ($start < $earliest) {
                continue;
            }
            if ($this->overlapCount($existing, $start, $start->add($duration)) < $this->rules['engineers']) {
                $slots[] = $start;
            }
        }
        return $slots;
    }

    public function isAvailable(DateTimeImmutable $start, int $durationMinutes, DateTimeImmutable $now): bool
    {
        foreach ($this->slotsFor($start, $durationMinutes, $now) as $slot) {
            if ($slot == $start) {
                return true;
            }
        }
        return false;
    }

    /** @return list<DateTimeImmutable> days within the booking window that are open */
    public function openDays(DateTimeImmutable $now): array
    {
        $days = [];
        $day = $now->setTime(0, 0);
        for ($i = 0; $i <= $this->rules['max_days_ahead']; $i++) {
            if (isset($this->rules['opening_hours'][(int) $day->format('N')])) {
                $days[] = $day;
            }
            $day = $day->modify('+1 day');
        }
        return $days;
    }

    private function withinWindow(DateTimeImmutable $day, DateTimeImmutable $now): bool
    {
        $last = $now->setTime(0, 0)->modify('+' . $this->rules['max_days_ahead'] . ' days');
        return $day >= $now->setTime(0, 0) && $day <= $last;
    }

    private function at(DateTimeImmutable $day, string $time): DateTimeImmutable
    {
        [$h, $m] = array_map('intval', explode(':', $time));
        return $day->setTime($h, $m);
    }

    /** @param list<array{starts_at:string, ends_at:string}> $existing */
    private function overlapCount(array $existing, DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        $count = 0;
        foreach ($existing as $b) {
            // Two jobs overlap when each starts before the other ends.
            if (new DateTimeImmutable($b['starts_at']) < $end && new DateTimeImmutable($b['ends_at']) > $start) {
                $count++;
            }
        }
        return $count;
    }
}
