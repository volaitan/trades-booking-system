<form method="post" action="<?= e($action) ?>" class="inline status-form">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <select name="status" class="auto-submit" aria-label="Status">
        <?php foreach ($options as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $opt === $current ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
        <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-small">Save</button></noscript>
</form>
