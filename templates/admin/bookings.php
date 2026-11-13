<h1>Bookings</h1>
<nav class="filters">
    <?php foreach (['' => 'All upcoming', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label): ?>
        <a href="/admin/bookings<?= $key ? '?status=' . $key . '&all=1' : '' ?>" class="<?= ($status ?? '') === $key ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</nav>
<?php if (!$bookings): ?>
    <p class="muted">Nothing here.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Ref</th><th>When</th><th>Customer</th><th>Service</th><th>Address</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
        <tr>
            <td><code><?= e($b['reference']) ?></code></td>
            <td><?= ukdate($b['starts_at'], 'D j M Y, g:ia') ?></td>
            <td><?= e($b['customer_name']) ?><br><small><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a> · <?= e($b['phone']) ?></small></td>
            <td><?= e($b['service_name']) ?><?php if ($b['notes'] !== ''): ?><br><small class="muted"><?= e($b['notes']) ?></small><?php endif; ?></td>
            <td><?= e($b['address']) ?>, <?= e($b['postcode']) ?></td>
            <td><?php $action = "/admin/bookings/{$b['id']}/status"; $options = \App\Repositories\BookingRepository::STATUSES; $current = $b['status']; require __DIR__ . '/_status_form.php'; ?></td>
            <td><a href="/admin/quotes/new?booking=<?= (int) $b['id'] ?>">Quote</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
