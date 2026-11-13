<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\BookingRepository;
use App\Repositories\ServiceRepository;
use App\ValidationException;
use DateTimeImmutable;
use PDO;

final class BookingService
{
    public function __construct(
        private readonly PDO $db,
        private readonly BookingRepository $bookings,
        private readonly ServiceRepository $services,
        private readonly AvailabilityService $availability,
    ) {
    }

    /**
     * Validate customer input and create a pending booking.
     *
     * @param array<string, mixed> $input raw form data
     * @return array<string, mixed> the stored booking
     * @throws ValidationException
     */
    public function book(array $input, DateTimeImmutable $now): array
    {
        $errors = [];
        $field = static fn (string $k): string => trim((string) ($input[$k] ?? ''));

        $service = $this->services->find((int) ($input['service_id'] ?? 0));
        if ($service === null || !$service['active']) {
            $errors['service_id'] = 'Please choose a service.';
        }
        if ($field('customer_name') === '' || mb_strlen($field('customer_name')) > 100) {
            $errors['customer_name'] = 'Please enter your name.';
        }
        if (!filter_var($field('email'), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (!preg_match('/^[0-9 +()-]{10,20}$/', $field('phone'))) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }
        if ($field('address') === '') {
            $errors['address'] = 'Please enter the address for the job.';
        }
        $postcode = self::normalisePostcode($field('postcode'));
        if ($postcode === null) {
            $errors['postcode'] = 'Please enter a valid UK postcode.';
        }

        $when = $field('date') . ' ' . $field('time');
        $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $when);
        // Reject impossible values like 2026-02-30 or 25:00 that PHP would otherwise roll over.
        if ($start === false || $start->format('Y-m-d H:i') !== $when) {
            $errors['time'] = 'Please choose a date and time.';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        $duration = (int) $service['duration_minutes'];
        $end = $start->modify("+{$duration} minutes");

        // Check and insert inside one transaction so two customers can't grab the same slot.
        $this->db->beginTransaction();
        try {
            if (!$this->availability->isAvailable($start, $duration, $now)) {
                throw new ValidationException(['time' => 'Sorry, that time is no longer available. Please pick another.']);
            }
            $id = $this->bookings->create([
                'reference'     => self::newReference(),
                'service_id'    => (int) $service['id'],
                'customer_name' => $field('customer_name'),
                'email'         => strtolower($field('email')),
                'phone'         => $field('phone'),
                'address'       => $field('address'),
                'postcode'      => $postcode,
                'notes'         => mb_substr($field('notes'), 0, 2000),
                'starts_at'     => $start->format('Y-m-d H:i:s'),
                'ends_at'       => $end->format('Y-m-d H:i:s'),
            ]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->bookings->find($id);
    }

    /** Uppercase and space a UK postcode ("ch441ab" -> "CH44 1AB"), or null if invalid. */
    public static function normalisePostcode(string $postcode): ?string
    {
        $pc = strtoupper(preg_replace('/\s+/', '', $postcode) ?? '');
        if (!preg_match('/^[A-Z]{1,2}\d[A-Z\d]?\d[A-Z]{2}$/', $pc)) {
            return null;
        }
        return substr($pc, 0, -3) . ' ' . substr($pc, -3);
    }

    /** Short, unambiguous reference for customers to quote, e.g. BK-7KQ4M2. */
    public static function newReference(string $prefix = 'BK'): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $ref = '';
        for ($i = 0; $i < 6; $i++) {
            $ref .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return "$prefix-$ref";
    }
}
