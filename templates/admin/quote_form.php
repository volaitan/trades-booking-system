<?php
$items = array_values($old['items'] ?? []);
while (count($items) < 5) {
    $items[] = ['description' => '', 'quantity' => 1, 'unit_price' => ''];
}
?>
<h1>New quote</h1>
<?php if ($errors): ?><div class="alert alert-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="post" action="/admin/quotes" class="card form">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="booking_id" value="<?= e($old['booking_id'] ?? '') ?>">
    <div class="row">
        <label>Customer name <input name="customer_name" value="<?= e($old['customer_name'] ?? '') ?>" required></label>
        <label>Email <input name="email" type="email" value="<?= e($old['email'] ?? '') ?>" required></label>
    </div>
    <label>Address <input name="address" value="<?= e($old['address'] ?? '') ?>"></label>

    <table class="table items">
        <thead><tr><th>Description</th><th>Qty</th><th>Unit price (£)</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><input name="items[<?= $i ?>][description]" value="<?= e($item['description']) ?>" aria-label="Description"></td>
                <td><input name="items[<?= $i ?>][quantity]" type="number" min="1" value="<?= e($item['quantity']) ?>" aria-label="Quantity"></td>
                <td><input name="items[<?= $i ?>][unit_price]" inputmode="decimal" value="<?= e($item['unit_price']) ?>" aria-label="Unit price" placeholder="0.00"></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row">
        <label>VAT rate (%) <input name="vat_rate" type="number" min="0" max="100" value="<?= e($old['vat_rate'] ?? $vatRate) ?>"></label>
        <label>Notes for the customer <textarea name="notes" rows="2"><?= e($old['notes'] ?? '') ?></textarea></label>
    </div>
    <button class="btn">Create quote</button>
</form>
