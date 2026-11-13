<?php $err = static fn (string $k) => isset($errors[$k]) ? '<p class="error">' . e($errors[$k]) . '</p>' : ''; ?>
<section class="hero-small">
    <h1>Book a visit</h1>
    <p>Pick a service and a time that suits you. We'll confirm by email.</p>
</section>

<?php if (isset($errors['form'])): ?><div class="alert alert-error"><?= e($errors['form']) ?></div><?php endif; ?>

<form method="post" action="/book" class="card form" id="booking-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <fieldset>
        <legend>1. What do you need?</legend>
        <div class="service-grid">
            <?php foreach ($services as $s): ?>
                <label class="service-option">
                    <input type="radio" name="service_id" value="<?= (int) $s['id'] ?>"
                        <?= (string) ($old['service_id'] ?? '') === (string) $s['id'] ? 'checked' : '' ?>>
                    <span>
                        <strong><?= e($s['name']) ?></strong>
                        <small><?= (int) $s['duration_minutes'] ?> mins · from <?= money($s['price_pence']) ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= $err('service_id') ?>
    </fieldset>

    <fieldset>
        <legend>2. When?</legend>
        <div class="row">
            <label>Date
                <select name="date" id="date">
                    <option value="">Choose a day…</option>
                    <?php foreach ($days as $d): ?>
                        <option value="<?= $d->format('Y-m-d') ?>" <?= ($old['date'] ?? '') === $d->format('Y-m-d') ? 'selected' : '' ?>>
                            <?= $d->format('D j M') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Time
                <select name="time" id="time" data-selected="<?= e($old['time'] ?? '') ?>">
                    <option value="">Choose a service and day first</option>
                </select>
            </label>
        </div>
        <?= $err('time') ?>
    </fieldset>

    <fieldset>
        <legend>3. Your details</legend>
        <div class="row">
            <label>Name <input name="customer_name" value="<?= e($old['customer_name'] ?? '') ?>" autocomplete="name" required></label>
            <label>Phone <input name="phone" type="tel" value="<?= e($old['phone'] ?? '') ?>" autocomplete="tel" required></label>
        </div>
        <?= $err('customer_name') ?><?= $err('phone') ?>
        <label>Email <input name="email" type="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required></label>
        <?= $err('email') ?>
        <div class="row">
            <label>Address <input name="address" value="<?= e($old['address'] ?? '') ?>" autocomplete="street-address" required></label>
            <label>Postcode <input name="postcode" value="<?= e($old['postcode'] ?? '') ?>" autocomplete="postal-code" required></label>
        </div>
        <?= $err('address') ?><?= $err('postcode') ?>
        <label>Anything we should know? <textarea name="notes" rows="3"><?= e($old['notes'] ?? '') ?></textarea></label>
    </fieldset>

    <button class="btn" type="submit">Request booking</button>
</form>
