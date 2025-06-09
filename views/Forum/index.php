<div class="container">
    <h1 class="mb-4">Форум</h1>

    <div class="row">
        <div class="col-lg-8">
            <?php foreach ($categories as $category): ?>
                <div class="mb-4 border rounded p-3 bg-light">
                    <h2 class="h5 mb-2"><?= htmlspecialchars($category['name']) ?></h2>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($category['description'])) ?></p>

                    <?php if (!empty($subcategoriesGrouped[$category['id']])): ?>
                        <ul class="list-group">
                            <?php foreach ($subcategoriesGrouped[$category['id']] as $subcategory): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">
                                            <a href="/forum/<?= htmlspecialchars($subcategory['slug']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($subcategory['name']) ?>
                                            </a>
                                        </div>
                                        <?= nl2br(htmlspecialchars($subcategory['description'])) ?>

                                        <small class="text-muted d-block mt-1">
                                            Тем: <?= $countThreads[$subcategory['id']] ?? 0 ?> |
                                            Повідомлень: <?= $countMessages[$subcategory['id']] ?? 0 ?> |
                                            Останній активний: <?= htmlspecialchars($lastActiveUser[$subcategory['id']]['username'] ?? '—') ?>,
                                            <?= htmlspecialchars($lastActiveUser[$subcategory['id']]['created_at'] ?? '—') ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">
                                        <?= $countThreads[$subcategory['id']] ?? 0 ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-info mt-2">Підкатегорій не знайдено.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-4">
            <?php include_once(__DIR__ . "/../components/birthday.php") ?>

            <div class="mb-4">
                <h5>Останні дописи</h5>
                <ul class="list-group">
                    <?php foreach ($latestPosts as $post): ?>
                        <li class="list-group-item">
                            <div>
                                <a href="/forum/thread/<?= $post['id'] ?>" class="fw-bold text-decoration-none">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </div>
                            <small class="text-muted">
                                Автор: <?= htmlspecialchars($post['username']) ?> |
                                <?= htmlspecialchars($post['created_at']) ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($latestPosts)): ?>
                        <li class="list-group-item text-muted">Постів поки немає.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div>
                <h5>Статистика</h5>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Теми
                        <span class="badge bg-secondary rounded-pill"><?= $statistics['threads'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Повідомлення
                        <span class="badge bg-secondary rounded-pill"><?= $statistics['messages'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Учасники
                        <span class="badge bg-secondary rounded-pill"><?= $statistics['members'] ?? 0 ?></span>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</div>