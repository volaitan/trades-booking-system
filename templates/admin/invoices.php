<h1>Invoices</h1>
<div class="stats">
    <div class="stat"><span><?= money($money['outstanding']) ?></span>outstanding</div>
    <div class="stat <?= $money['overdue'] > 0 ? 'stat-warn' : '' ?>"><span><?= money($money['overdue']) ?></span>overdue</div>
    <div class="stat"><span><?= money($money['paid_this_month']) ?></span>paid this month</div>
</div>
<?php if (!$invoices): ?>
    <p class="muted">No invoices yet. Accept a quote to create one.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Number</th><th>Customer</th><th>Issued</th><th>Due</th><th class="num">Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($invoices as $inv): ?>
        <tr>
            <td><a href="/admin/invoices/<?= (int) $inv['id'] ?>"><code><?= e($inv['number']) ?></code></a></td>
            <td><?= e($inv['customer_name']) ?></td>
            <td><?= ukdate($inv['issue_date'], 'j M Y') ?></td>
            <td><?= ukdate($inv['due_date'], 'j M Y') ?></td>
            <td class="num"><?= money($inv['total_pence']) ?></td>
            <td>
                <?php if ($inv['status'] === 'paid'): ?><span class="pill pill-paid">paid</span>
                <?php elseif ($inv['overdue']): ?><span class="pill pill-overdue">overdue</span>
                <?php else: ?><span class="pill pill-unpaid">unpaid</span><?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
