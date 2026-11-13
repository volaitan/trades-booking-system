<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice <?= e($invoice['number']) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="/assets/invoice.css">
</head>
<body class="invoice-page">
<div class="toolbar no-print">
    <a href="/admin/invoices">← Invoices</a>
    <?php if (!empty($flash)): ?><span class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></span><?php endif; ?>
    <span class="spacer"></span>
    <?php if ($invoice['status'] === 'unpaid'): ?>
        <form method="post" action="/admin/invoices/<?= (int) $invoice['id'] ?>/pay" class="inline">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button class="btn btn-ghost">Mark as paid</button>
        </form>
    <?php endif; ?>
    <button class="btn" id="print-btn">Print / Save as PDF</button>
</div>

<article class="invoice">
    <header class="invoice-head">
        <div>
            <h1><?= e($business['name']) ?></h1>
            <p><?= e($business['address']) ?><br><?= e($business['phone']) ?> · <?= e($business['email']) ?><br>VAT no. <?= e($business['vat_number']) ?></p>
        </div>
        <div class="invoice-meta">
            <h2>INVOICE</h2>
            <p><strong><?= e($invoice['number']) ?></strong></p>
            <p>Issued: <?= ukdate($invoice['issue_date'], 'j F Y') ?><br>Due: <?= ukdate($invoice['due_date'], 'j F Y') ?></p>
            <?php if ($invoice['status'] === 'paid'): ?><p class="stamp">PAID <?= ukdate((string) $invoice['paid_on'], 'j M Y') ?></p>
            <?php elseif ($invoice['overdue']): ?><p class="stamp stamp-overdue">OVERDUE</p><?php endif; ?>
        </div>
    </header>

    <section class="bill-to">
        <h3>Bill to</h3>
        <p><?= e($invoice['customer_name']) ?><br><?= e($invoice['address']) ?><br><?= e($invoice['email']) ?></p>
    </section>

    <?php require __DIR__ . '/_items_table.php'; ?>

    <footer class="invoice-foot">
        <p>Please pay within the agreed terms, quoting <strong><?= e($invoice['number']) ?></strong> as the reference.</p>
        <p>Thank you for your business.</p>
    </footer>
</article>
<script src="/assets/app.js" defer></script>
</body>
</html>
