<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\QuoteRepository;
use DateTimeImmutable;
use DomainException;
use PDO;

final class InvoiceService
{
    public function __construct(
        private readonly PDO $db,
        private readonly QuoteRepository $quotes,
        private readonly int $paymentTermsDays = 14,
    ) {
    }

    /**
     * Turn an accepted quote into an invoice. Line items and totals are copied, so later
     * edits to the quote can never change an invoice that has already been issued.
     */
    public function invoiceFromQuote(int $quoteId, DateTimeImmutable $today): int
    {
        $quote = $this->quotes->find($quoteId);
        if ($quote === null) {
            throw new DomainException('Quote not found');
        }
        if ($quote['status'] !== 'accepted') {
            throw new DomainException('Only accepted quotes can be invoiced');
        }
        $existing = $this->db->prepare('SELECT number FROM invoices WHERE quote_id = ?');
        $existing->execute([$quoteId]);
        if ($number = $existing->fetchColumn()) {
            throw new DomainException("Quote already invoiced as $number");
        }

        $t = $quote['totals'];
        $this->db->beginTransaction();
        try {
            $number = $this->nextNumber($today);
            $this->db->prepare(
                'INSERT INTO invoices (number, quote_id, customer_name, email, address, issue_date, due_date, vat_rate, subtotal_pence, vat_pence, total_pence)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $number, $quoteId, $quote['customer_name'], $quote['email'], $quote['address'],
                $today->format('Y-m-d'), $today->modify("+{$this->paymentTermsDays} days")->format('Y-m-d'),
                $quote['vat_rate'], $t['subtotal'], $t['vat'], $t['total'],
            ]);
            $invoiceId = (int) $this->db->lastInsertId();
            $stmt = $this->db->prepare('INSERT INTO invoice_items (invoice_id, description, quantity, unit_price_pence) VALUES (?, ?, ?, ?)');
            foreach ($quote['items'] as $item) {
                $stmt->execute([$invoiceId, $item['description'], $item['quantity'], $item['unit_price_pence']]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $invoiceId;
    }

    /** Sequential per year with no gaps: INV-2026-0001, INV-2026-0002, ... */
    public function nextNumber(DateTimeImmutable $today): string
    {
        $prefix = 'INV-' . $today->format('Y') . '-';
        $stmt = $this->db->prepare('SELECT number FROM invoices WHERE number LIKE ? ORDER BY number DESC LIMIT 1');
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();
        $next = $last ? (int) substr((string) $last, strlen($prefix)) + 1 : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id, ?DateTimeImmutable $today = null): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM invoices WHERE id = ?');
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            return null;
        }
        $items = $this->db->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id');
        $items->execute([$id]);
        $invoice['items'] = $items->fetchAll();
        $invoice['overdue'] = $this->isOverdue($invoice, $today ?? new DateTimeImmutable('today'));
        return $invoice;
    }

    /** @return list<array<string, mixed>> */
    public function all(DateTimeImmutable $today): array
    {
        $rows = $this->db->query('SELECT * FROM invoices ORDER BY issue_date DESC, number DESC')->fetchAll();
        foreach ($rows as &$row) {
            $row['overdue'] = $this->isOverdue($row, $today);
        }
        return $rows;
    }

    public function markPaid(int $id, DateTimeImmutable $on): void
    {
        $this->db->prepare("UPDATE invoices SET status = 'paid', paid_on = ? WHERE id = ? AND status = 'unpaid'")
            ->execute([$on->format('Y-m-d'), $id]);
    }

    /** @return array{outstanding:int, overdue:int, paid_this_month:int} totals in pence */
    public function summary(DateTimeImmutable $today): array
    {
        $out = ['outstanding' => 0, 'overdue' => 0, 'paid_this_month' => 0];
        foreach ($this->all($today) as $inv) {
            if ($inv['status'] === 'unpaid') {
                $out['outstanding'] += (int) $inv['total_pence'];
                if ($inv['overdue']) {
                    $out['overdue'] += (int) $inv['total_pence'];
                }
            } elseif ($inv['paid_on'] !== null && substr((string) $inv['paid_on'], 0, 7) === $today->format('Y-m')) {
                $out['paid_this_month'] += (int) $inv['total_pence'];
            }
        }
        return $out;
    }

    /** @param array<string, mixed> $invoice */
    private function isOverdue(array $invoice, DateTimeImmutable $today): bool
    {
        return $invoice['status'] === 'unpaid' && substr((string) $invoice['due_date'], 0, 10) < $today->format('Y-m-d');
    }
}
