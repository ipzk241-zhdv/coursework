<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Теми: <?= htmlspecialchars($subcategory['name']) ?></h2>
        <a href="/forum/createthread?subcategory=<?= urlencode($subcategorySlug) ?>" class="btn btn-primary">
            + Створити тему
        </a>
    </div>

    <?php if (!empty($threads)): ?>
        <div class="list-group">
            <?php foreach ($threads as $thread): ?>
                <?php
                $isPinned = !is_null($thread['pinned']) && $thread['pinned'] !== 0;
                $pinnedClass = $isPinned ? 'bg-warning-subtle border-warning' : '';
                ?>
                <a href="/forum/<?= urlencode($subcategorySlug) ?>/<?= $thread['id'] ?>"
                    class="list-group-item list-group-item-action <?= $pinnedClass ?>">
                    <div class="d-flex justify-content-between align-items-center">

                        <!-- Ліва частина -->
                        <div class="d-flex align-items-center flex-grow-1">
                            <div>
                                <h5 class="mb-1">
                                    <?= htmlspecialchars($thread['title']) ?>
                                    <?php if ($isPinned): ?>
                                        <i class="bi bi-pin-fill text-warning" title="Закріплений тред"></i>
                                    <?php endif; ?>
                                </h5>
                                <p class="mb-0 text-muted">
                                    Автор: <?= htmlspecialchars($thread['author_name']) ?>,
                                    <?= date('d.m.Y', strtotime($thread['created_at'])) ?>
                                </p>
                            </div>
                        </div>

                        <!-- Права частина -->
                        <?php if (!empty($thread['last_comment_user'])): ?>
                            <div class="text-end d-flex flex-column align-items-end">
                                <div class="d-flex align-items-center mb-1">
                                    <div class="me-2 text-muted text-center">
                                        <div><?= $thread['comments_count'] ?? 0 ?> коментарів</div>
                                        <div><?= $thread['likes_count'] ?? 0 ?> лайків</div>
                                    </div>
                                    <img
                                        src="/public/users/<?= htmlspecialchars($thread['last_comment_avatar'] ?? 'default.png') ?>"
                                        alt="Аватар останнього"
                                        class="rounded-circle"
                                        width="48"
                                        height="48">
                                </div>
                                <div class="small text-muted">
                                    <?= htmlspecialchars($thread['last_comment_user']) ?>,
                                    <?= date('d.m.Y', strtotime($thread['last_comment_date'])) ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-end text-muted">Коментарів ще немає</div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>

        </div>

        <!-- Пагінація -->
        <nav aria-label="Навігація сторінками" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?subcategory=<?= urlencode($subcategorySlug) ?>&page=<?= $i ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php else: ?>
        <div class="alert alert-info">У цій підкатегорії поки немає тем.</div>
    <?php endif; ?>
</div>