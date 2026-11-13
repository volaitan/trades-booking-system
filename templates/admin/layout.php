<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin') ?> | Admin</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="admin">
<header class="topbar">
    <div class="wrap topbar-inner">
        <a class="brand" href="/admin"><?= e($business['name']) ?> <span class="badge">Admin</span></a>
        <?php if (!empty($user)): ?>
        <nav class="admin-nav">
            <a href="/admin">Dashboard</a>
            <a href="/admin/bookings">Bookings</a>
            <a href="/admin/quotes">Quotes</a>
            <a href="/admin/invoices">Invoices</a>
            <form method="post" action="/admin/logout" class="inline">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button class="link">Sign out</button>
            </form>
        </nav>
        <?php endif; ?>
    </div>
</header>
<main class="wrap">
    <?php if (!empty($flash)): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
    <?= $content ?>
</main>
<script src="/assets/app.js" defer></script>
</body>
</html>
