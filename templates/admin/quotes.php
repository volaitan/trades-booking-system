<div class="page-head">
    <h1>Quotes</h1>
    <a class="btn" href="/admin/quotes/new">+ New quote</a>
</div>
<?php if (!$quotes): ?>
    <p class="muted">No quotes yet.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Ref</th><th>Customer</th><th>Created</th><th class="num">Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($quotes as $q): ?>
        <tr>
            <td><a href="/admin/quotes/<?= (int) $q['id'] ?>"><code><?= e($q['reference']) ?></code></a></td>
            <td><?= e($q['customer_name']) ?></td>
            <td><?= ukdate($q['created_at'], 'j M Y') ?></td>
            <td class="num"><?= money($q['totals']['total']) ?></td>
            <td><span class="pill pill-<?= e($q['status']) ?>"><?= e($q['status']) ?></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
