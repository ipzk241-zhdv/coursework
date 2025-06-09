<?php require __DIR__ . '/header.php'; ?>
<div class="d-flex">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="flex-grow-1 p-4" style="min-height: 100vh; background-color: #f8f9fa;">
        <?= $content ?? '' ?>
    </main>
</div>
<?php require __DIR__ . '/footer.php'; ?>