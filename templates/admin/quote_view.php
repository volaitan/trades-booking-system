<div class="page-head">
    <h1>Quote <?= e($quote['reference']) ?></h1>
    <span class="pill pill-<?= e($quote['status']) ?>"><?= e($quote['status']) ?></span>
</div>
<div class="card">
    <p><strong><?= e($quote['customer_name']) ?></strong><br><?= e($quote['email']) ?><br><?= e($quote['address']) ?></p>
    <?php require __DIR__ . '/_items_table.php'; ?>
    <?php if ($quote['notes'] !== ''): ?><p class="muted"><?= nl2br(e($quote['notes'])) ?></p><?php endif; ?>
</div>
<div class="actions">
    <?php $action = "/admin/quotes/{$quote['id']}/status"; $options = \App\Repositories\QuoteRepository::STATUSES; $current = $quote['status']; require __DIR__ . '/_status_form.php'; ?>
    <?php if ($quote['status'] === 'accepted'): ?>
        <form method="post" action="/admin/quotes/<?= (int) $quote['id'] ?>/invoice" class="inline">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button class="btn">Create invoice</button>
        </form>
    <?php else: ?>
        <span class="muted">Mark the quote as accepted to invoice it.</span>
    <?php endif; ?>
</div>
