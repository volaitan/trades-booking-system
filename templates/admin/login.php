<section class="card narrow">
    <h1>Sign in</h1>
    <form method="post" action="/admin/login" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label>Email <input name="email" type="email" autocomplete="username" required></label>
        <label>Password <input name="password" type="password" autocomplete="current-password" required></label>
        <?php if (isset($errors['email'])): ?><p class="error"><?= e($errors['email']) ?></p><?php endif; ?>
        <button class="btn">Sign in</button>
    </form>
</section>
