<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\BookingService;
use App\Services\QuoteCalculator;
use App\ValidationException;
use PDO;

final class QuoteRepository
{
    public const STATUSES = ['draft', 'sent', 'accepted', 'declined'];

    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * @param array{customer_name:string, email:string, address?:string, notes?:string, booking_id?:int|null, vat_rate:int} $quote
     * @param list<array{description:string, quantity:int, unit_price_pence:int}> $items
     */
    public function create(array $quote, array $items): int
    {
        $errors = [];
        if (trim($quote['customer_name']) === '') {
            $errors['customer_name'] = 'Customer name is required.';
        }
        if (!filter_var($quote['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        $items = array_values(array_filter($items, static fn ($i) => trim($i['description']) !== ''));
        if (!$items) {
            $errors['items'] = 'Add at least one line item.';
        }
        foreach ($items as $i) {
            if ($i['quantity'] < 1 || $i['unit_price_pence'] < 0) {
                $errors['items'] = 'Quantities must be at least 1 and prices cannot be negative.';
            }
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        $this->db->beginTransaction();
        $this->db->prepare(
            'INSERT INTO quotes (reference, booking_id, customer_name, email, address, notes, vat_rate) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            BookingService::newReference('QT'),
            $quote['booking_id'] ?? null,
            trim($quote['customer_name']),
            strtolower(trim($quote['email'])),
            trim($quote['address'] ?? ''),
            trim($quote['notes'] ?? ''),
            $quote['vat_rate'],
        ]);
        $id = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare('INSERT INTO quote_items (quote_id, description, quantity, unit_price_pence) VALUES (?, ?, ?, ?)');
        foreach ($items as $i) {
            $stmt->execute([$id, trim($i['description']), $i['quantity'], $i['unit_price_pence']]);
        }
        $this->db->commit();
        return $id;
    }

    /** @return array<string, mixed>|null quote with 'items' and 'totals' */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM quotes WHERE id = ?');
        $stmt->execute([$id]);
        $quote = $stmt->fetch();
        if (!$quote) {
            return null;
        }
        $items = $this->db->prepare('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY id');
        $items->execute([$id]);
        $quote['items'] = $items->fetchAll();
        $quote['totals'] = QuoteCalculator::totals($quote['items'], (int) $quote['vat_rate']);
        return $quote;
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $quotes = $this->db->query('SELECT id FROM quotes ORDER BY id DESC')->fetchAll(PDO::FETCH_COLUMN);
        return array_map(fn ($id) => $this->find((int) $id), $quotes);
    }

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Unknown status $status");
        }
        $this->db->prepare('UPDATE quotes SET status = ? WHERE id = ?')->execute([$status, $id]);
    }
}
