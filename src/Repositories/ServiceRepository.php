<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ServiceRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function active(): array
    {
        return $this->db->query('SELECT * FROM services WHERE active = 1 ORDER BY name')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, int $durationMinutes, int $pricePence, string $description = ''): int
    {
        $this->db->prepare('INSERT INTO services (name, description, duration_minutes, price_pence) VALUES (?, ?, ?, ?)')
            ->execute([$name, $description, $durationMinutes, $pricePence]);
        return (int) $this->db->lastInsertId();
    }
}
