<?php
$doc = $quote ?? $invoice;
$t = \App\Services\QuoteCalculator::totals($doc['items'], (int) $doc['vat_rate']);
// Invoices show the totals stored when they were issued, never a recalculation.
if (isset($doc['total_pence'])) {
    $t = ['subtotal' => (int) $doc['subtotal_pence'], 'vat' => (int) $doc['vat_pence'], 'total' => (int) $doc['total_pence']] + $t;
}
?>
<table class="table items-view">
    <thead><tr><th>Description</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th></tr></thead>
    <tbody>
    <?php foreach ($doc['items'] as $i => $item): ?>
        <tr>
            <td><?= e($item['description']) ?></td>
            <td class="num"><?= (int) $item['quantity'] ?></td>
            <td class="num"><?= money($item['unit_price_pence']) ?></td>
            <td class="num"><?= money($t['lines'][$i]) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><td colspan="3" class="num">Subtotal</td><td class="num"><?= money($t['subtotal']) ?></td></tr>
        <tr><td colspan="3" class="num">VAT (<?= (int) $doc['vat_rate'] ?>%)</td><td class="num"><?= money($t['vat']) ?></td></tr>
        <tr class="grand"><td colspan="3" class="num">Total</td><td class="num"><?= money($t['total']) ?></td></tr>
    </tfoot>
</table>
