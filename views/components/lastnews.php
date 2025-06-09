<?php

use classes\Core;

$lastnews = Core::getInstance()->db->selectQuery(
    "SELECT id, title, content, created_at FROM threads ORDER BY id DESC LIMIT 3"
);

?>

<?php if (empty($lastnews)) : ?>
    <!-- Останні новини -->
    <section class="container py-5 mt-5"></section>

<?php else : ?>

    <!-- Останні новини -->
    <section class="container py-5 mt-5 text-center">
        <h3 class="mb-4">Останні новини</h3>
        <div class="row justify-content-center">
            <?php foreach ($lastnews as $news): ?>
                <div class="col-md-4 mb-3 d-flex align-items-stretch">
                    <div class="card w-100 h-100">
                        <?php if (!empty($news['cover'])): ?>
                            <img src="<?= htmlspecialchars($news['cover']) ?>" class="card-img-top" alt="Зображення новини">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($news['title']) ?></h5>
                            <p class="card-text flex-grow-1"><?= mb_substr(strip_tags($news['content']), 0, 100) ?>...</p>
                            <a href="/news/view?id=<?= urlencode($news['id']) ?>" class="btn btn-primary mt-auto">Читати</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

<?php endif; ?>