<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Book') ?> | <?= e($business['name']) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="wrap topbar-inner">
        <a class="brand" href="/"><?= e($business['name']) ?></a>
        <a class="phone" href="tel:<?= e(preg_replace('/\s+/', '', $business['phone'])) ?>">📞 <?= e($business['phone']) ?></a>
    </div>
</header>
<main class="wrap">
    <?= $content ?>
</main>
<footer class="wrap footer">© <?= date('Y') ?> <?= e($business['name']) ?> · <?= e($business['address']) ?></footer>
<script src="/assets/app.js" defer></script>
</body>
</html>
