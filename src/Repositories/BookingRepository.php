<?php
declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use PDO;

final class BookingRepository
{
    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];
    private const ACTIVE = "('pending','confirmed')";

    public function __construct(private readonly PDO $db)
    {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bookings (reference, service_id, customer_name, email, phone, address, postcode, notes, starts_at, ends_at, status)
             VALUES (:reference, :service_id, :customer_name, :email, :phone, :address, :postcode, :notes, :starts_at, :ends_at, :status)'
        );
        $stmt->execute($data + ['status' => 'pending']);
        return (int) $this->db->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT b.*, s.name AS service_name, s.price_pence FROM bookings b JOIN services s ON s.id = b.service_id WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByReference(string $reference): ?array
    {
        $stmt = $this->db->prepare('SELECT id FROM bookings WHERE reference = ?');
        $stmt->execute([$reference]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : $this->find((int) $id);
    }

    /** Pending and confirmed bookings that touch the given period. */
    public function activeBetween(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $stmt = $this->db->prepare(
            'SELECT starts_at, ends_at FROM bookings WHERE status IN ' . self::ACTIVE . ' AND starts_at < ? AND ends_at > ?'
        );
        $stmt->execute([$to->format('Y-m-d H:i:s'), $from->format('Y-m-d H:i:s')]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function list(?string $status = null, ?string $fromDate = null): array
    {
        $sql = 'SELECT b.*, s.name AS service_name FROM bookings b JOIN services s ON s.id = b.service_id WHERE 1=1';
        $params = [];
        if ($status !== null && in_array($status, self::STATUSES, true)) {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }
        if ($fromDate !== null) {
            $sql .= ' AND b.starts_at >= ?';
            $params[] = $fromDate . ' 00:00:00';
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY b.starts_at');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Unknown status $status");
        }
        $this->db->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public function countOn(string $date, array $statuses = ['pending', 'confirmed']): int
    {
        $in = implode(',', array_fill(0, count($statuses), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings WHERE starts_at >= ? AND starts_at <= ? AND status IN ($in)");
        $stmt->execute([$date . ' 00:00:00', $date . ' 23:59:59', ...$statuses]);
        return (int) $stmt->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM bookings WHERE status = ?');
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }
}
