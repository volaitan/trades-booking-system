<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Totals for quotes and invoices. All amounts are in pence.
 * VAT is calculated once on the subtotal (not per line) and rounded half-up, as HMRC allows.
 */
final class QuoteCalculator
{
    /**
     * @param list<array{quantity:int, unit_price_pence:int}> $items
     * @return array{subtotal:int, vat:int, total:int, lines:list<int>}
     */
    public static function totals(array $items, int $vatRatePercent): array
    {
        $lines = array_map(static fn (array $i): int => (int) $i['quantity'] * (int) $i['unit_price_pence'], $items);
        $subtotal = array_sum($lines);
        $vat = intdiv($subtotal * $vatRatePercent + 50, 100);
        return ['subtotal' => $subtotal, 'vat' => $vat, 'total' => $subtotal + $vat, 'lines' => $lines];
    }
}
