<?php

function normalizeParentId($parentId): int
{
    return empty($parentId) ? 0 : (int)$parentId;
}

function buildCommentIndex(array $comments): array
{
    $index = [];
    foreach ($comments as $comment) {
        $index[(int)$comment['id']] = $comment;
    }
    return $index;
}

function getTopAncestorId(int $id, array $commentById): int
{
    $current = $commentById[$id] ?? null;
    if (!$current) return $id;

    while ($current && normalizeParentId($current['parent_id']) !== 0) {
        $parentId = normalizeParentId($current['parent_id']);
        if (!isset($commentById[$parentId])) break;
        $current = $commentById[$parentId];
    }

    return (int)($current['id'] ?? $id);
}

$commentById = buildCommentIndex($comments);

$topAncestorOf = [];
foreach ($comments as $comment) {
    $id = (int)$comment['id'];
    $pid = normalizeParentId($comment['parent_id']);
    $topAncestorOf[$id] = $pid === 0 ? $id : getTopAncestorId($id, $commentById);
}

$topComments = [];
$childrenOfTop = [];

foreach ($comments as $comment) {
    $id = (int)$comment['id'];
    $pid = normalizeParentId($comment['parent_id']);

    if ($pid === 0) {
        $topComments[] = $comment;
    } else {
        $topId = $topAncestorOf[$id];
        $childrenOfTop[$topId][] = $comment;
    }
}

?>

<div class="container my-4">
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h2 class="card-title"><?= htmlspecialchars($thread['title']) ?></h2>
            <div class="d-flex align-items-center mb-2">
                <img src="/public/users/<?= htmlspecialchars($thread['author_avatar']) ?>" alt="Аватар автора" class="rounded-circle me-2" width="40" height="40">
                <small class="text-muted">
                    Автор: <?= htmlspecialchars($thread['author_name']) ?> |
                    Створено: <?= date('d.m.Y', strtotime($thread['created_at'])) ?>
                </small>
            </div>
            <hr>
            <div class="thread-content">
                <?= $thread['content'] ?>
            </div>
            <div class="d-flex align-items-center like-container">
                <button type="button"
                    class="btn btn-outline-primary btn-sm me-2 like-btn"
                    data-target-type="thread"
                    data-target-id="<?= $thread['id'] ?>"
                    data-type="like"
                    title="Лайкнути">
                    <i class="bi bi-hand-thumbs-up"></i>
                    <span class="like-count"><?= $thread['likes_count'] ?></span>
                </button>
                <button type="button"
                    class="btn btn-outline-danger btn-sm dislike-btn"
                    data-target-type="thread"
                    data-target-id="<?= $thread['id'] ?>"
                    data-type="dislike"
                    title="Дизлайкнути">
                    <i class="bi bi-hand-thumbs-down"></i>
                    <span class="dislike-count"><?= $thread['dislikes_count'] ?></span>
                </button>
            </div>
        </div>
    </div>

    <?php if (!empty($topComments)): ?>
        <div id="comments-container"></div>

        <!-- Пагінація -->
        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                            <a class="page-link" href="?id=<?= $thread['id'] ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info">Коментарів ще немає.</div>
    <?php endif; ?>

    <hr>

    <h4>Написати новий коментар</h4>
    <form id="comment-form">
        <textarea name="content" id="editor-root" rows="5"></textarea>
        <input type="hidden" name="thread_id" value="<?= $thread['id'] ?>">
        <input type="hidden" name="parent_id" value="0">
        <input type="hidden" name="add" value="1">
        <button type="submit" class="btn btn-primary mt-2">Надіслати</button>
    </form>
</div>

<script>
    window.FORUM_CONFIG = {
        threadId: <?= json_encode($thread['id']) ?>,
        topComments: <?= json_encode($topComments, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
        childrenOfTop: <?= json_encode($childrenOfTop, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
    };
</script>
<script src="/public/js/ajax-error-handler.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
<script src="/public/js/thread.js"></script>
<script>
    const FORUM_THREAD_ID = <?= json_encode($thread['id']) ?>;
    const COMMENT_POST_URL = <?= json_encode("/forum/{$thread['id']}/comment?ajax=1&add=1") ?>;
</script>
<script src="/public/js/comments.js"></script>