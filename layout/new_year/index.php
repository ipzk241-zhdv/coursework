<?php require 'header.php'; ?>
<div class="d-flex flex-column min-vh-100">
    <main class="flex-grow-1">
        <div class="bg-white bg-opacity-75 rounded shadow">
            <?= $content ?? '' ?>
        </div>
    </main>
</div>
<?php require 'footer.php'; ?>