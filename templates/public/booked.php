<section class="card center">
    <div class="tick">✔</div>
    <h1>Thanks, <?= e(explode(' ', $booking['customer_name'])[0]) ?>!</h1>
    <p>We've received your booking request and will confirm it by email shortly.</p>
    <dl class="summary">
        <dt>Reference</dt><dd><strong><?= e($booking['reference']) ?></strong></dd>
        <dt>Service</dt><dd><?= e($booking['service_name']) ?></dd>
        <dt>When</dt><dd><?= ukdate($booking['starts_at'], 'l j F Y, g:ia') ?></dd>
        <dt>Where</dt><dd><?= e($booking['address']) ?>, <?= e($booking['postcode']) ?></dd>
    </dl>
    <p class="muted">Need to change something? Call us on <?= e($business['phone']) ?> and quote your reference.</p>
</section>
