<?php require 'header.php'; ?>
<div class="d-flex flex-column min-vh-100">
    <main class="flex-grow-1">
        <?= $content ?? '' ?>
    </main>
</div>
<?php require 'footer.php'; ?>