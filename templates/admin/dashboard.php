<h1>Dashboard</h1>
<div class="stats">
    <div class="stat"><span><?= count($todayJobs) ?></span>jobs today</div>
    <div class="stat"><span><?= (int) $pending ?></span>awaiting confirmation</div>
    <div class="stat"><span><?= money($money['outstanding']) ?></span>outstanding</div>
    <div class="stat <?= $money['overdue'] > 0 ? 'stat-warn' : '' ?>"><span><?= money($money['overdue']) ?></span>overdue</div>
    <div class="stat"><span><?= money($money['paid_this_month']) ?></span>paid this month</div>
</div>

<h2>Coming up</h2>
<?php if (!$upcoming): ?>
    <p class="muted">No upcoming jobs.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>When</th><th>Customer</th><th>Service</th><th>Postcode</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($upcoming as $b): ?>
        <tr>
            <td><?= ukdate($b['starts_at'], 'D j M, g:ia') ?></td>
            <td><?= e($b['customer_name']) ?><br><small><?= e($b['phone']) ?></small></td>
            <td><?= e($b['service_name']) ?></td>
            <td><?= e($b['postcode']) ?></td>
            <td><span class="pill pill-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
            <td><a href="/admin/quotes/new?booking=<?= (int) $b['id'] ?>">Quote</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
