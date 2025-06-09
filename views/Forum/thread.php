<?php

use classes\Core;

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

// 1. Побудова індексу за ID
$commentById = buildCommentIndex($comments);

// 2. Визначення топ-предків
$topAncestorOf = [];
foreach ($comments as $comment) {
    $id = (int)$comment['id'];
    $pid = normalizeParentId($comment['parent_id']);
    $topAncestorOf[$id] = $pid === 0 ? $id : getTopAncestorId($id, $commentById);
}

// 3. Розбиття на топи та їх дочірні
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
                <?= $thread['content'] /* Переконайтесь, що контент безпечний або відфільтрований */ ?>
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
        <input type="hidden" name="user_id" value="<?= Core::getInstance()->session->get("user")['id'] ?>">
        <input type="hidden" name="add" value="1">
        <button type="submit" class="btn btn-primary mt-2">Надіслати</button>
    </form>
</div>


<!-- CKEditor 5 CDN -->
<script src="/public/js/ajax-error-handler.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
<script>
    class MyUploadAdapter {
        constructor(loader) {
            this.loader = loader;
        }

        upload() {
            return this.loader.file.then(file => {
                const data = new FormData();
                data.append('upload', file);

                return apiFetch('/forum/commentImage', {
                        method: 'POST',
                        body: data,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                    .then(result => ({
                        default: result.url
                    }))
                    .catch(error => {

                        return Promise.reject(error.message || 'Помилка завантаження');
                    });
            });
        }
    }

    function CustomUploadPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
            return new MyUploadAdapter(loader);
        };
    }

    let rootEditor;
    ClassicEditor
        .create(document.querySelector('#editor-root'), {
            extraPlugins: [CustomUploadPlugin],
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'imageUpload', '|', 'undo', 'redo']
        })
        .then(editor => {
            rootEditor = editor;
        })
        .catch(error => console.error(error));

    document.getElementById('comment-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        form.querySelector('[name="content"]').value = rootEditor.getData();

        const formData = new FormData(form);
        try {
            const response = await apiFetch("/forum/<?= $thread['id'] ?>/comment?ajax=1&add=1", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (response.edited) {
                location.reload();
            }
        } catch (err) {
            console.error('Помилка запиту:', err);
        }

    });

    function removeExistingReplyForms() {
        document.querySelectorAll('.reply-form').forEach(formEl => {
            const ed = formEl.ckeditorInstance;
            if (ed) {
                ed.destroy().catch(err => console.error(err));
            }
            formEl.parentElement.innerHTML = '';
        });
    }

    document.querySelectorAll('.reply-button').forEach(button => {
        button.addEventListener('click', function() {
            removeExistingReplyForms();
            const topId = this.getAttribute('data-top-id');
            const container = document.getElementById('reply-form-container-' + topId);

            const formHtml = `
                <form class="reply-form mt-2" data-top-id="${topId}">
                    <textarea rows="4"></textarea>
                    <input type="hidden" name="thread_id" value="<?= $thread['id'] ?>">
                    <input type="hidden" name="parent_id" value="${topId}"> 
                    <input type="hidden" name="user_id" value="<?= Core::getInstance()->session->get("user")['id'] ?>">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Відправити</button>
                    <button type="button" class="btn btn-sm btn-secondary mt-1 cancel-reply">Скасувати</button>
                </form>
            `;
            container.innerHTML = formHtml;

            const textarea = container.querySelector('textarea');
            ClassicEditor
                .create(textarea, {
                    extraPlugins: [CustomUploadPlugin],
                    toolbar: ['bold', 'italic', 'link', 'bulletedList', 'numberedList', 'imageUpload', '|', 'undo', 'redo']
                })
                .then(editor => {
                    container.querySelector('.reply-form').ckeditorInstance = editor;
                })
                .catch(error => console.error(error));

            container.querySelector('.cancel-reply').addEventListener('click', () => {
                const formEl = container.querySelector('.reply-form');
                const ed = formEl.ckeditorInstance;
                if (ed) {
                    ed.destroy()
                        .then(() => {
                            container.innerHTML = '';
                        })
                        .catch(err => console.error(err));
                } else {
                    container.innerHTML = '';
                }
            });

            container.querySelector('.reply-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const form = e.target;
                const editorInst = form.ckeditorInstance;
                const contentData = editorInst.getData();

                const formData = new FormData();
                formData.append('content', contentData);
                formData.append('thread_id', form.querySelector('[name="thread_id"]').value);
                formData.append('parent_id', form.querySelector('[name="parent_id"]').value);
                formData.append('user_id', form.querySelector('[name="user_id"]').value);
                formData.append('add', form.querySelector('[name="add"]').value);

                try {
                    const result = await apiFetch("/forum/<?= $thread['id'] ?>/comment?ajax=1&add=1", {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    if (result.edited) {
                        location.reload();
                    } else {
                        showError('Коментар не було збережено.');
                    }
                } catch (err) {
                    console.error("AJAX error:", err);
                }

            });
        });
    });
</script>
<script>
    const topComments = <?= json_encode($topComments, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    const childrenOfTop = <?= json_encode($childrenOfTop, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

    function renderComment(comment, indent = 0) {
        const div = document.createElement('div');
        div.className = 'border rounded p-3 mb-2';
        div.style.marginLeft = indent + 'px';

        const avatarUrl = comment.author_avatar ? '/public/users/' + comment.author_avatar : '/public/users/default.png';

        div.innerHTML = `
                <div style="display: flex; gap: 15px;">
                <div style="flex-shrink: 0; text-align: center; width: 80px;">
                    <img src="${avatarUrl}" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                    <div class="fw-bold mt-2" style="font-size: 0.9rem;">${escapeHTML(comment.author_name)}</div>
                </div>
                <div style="flex-grow: 1;">
                <div class="text-muted small mt-2">
                    Опубліковано ${formatDate(comment.created_at)}
                </div>
                    <div class="mt-4" style="white-space: pre-wrap;">${comment.content}</div>
                    <hr>
                <div class="d-flex align-items-center like-container">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm me-2 like-btn"
                            data-target-type="comment"
                            data-target-id="${comment.id}"
                            data-type="like"
                            title="Лайкнути">
                        <i class="bi bi-hand-thumbs-up"></i>
                        <span class="like-count">${comment.likes_count}</span>
                    </button>
                    <button type="button"
                            class="btn btn-outline-danger btn-sm dislike-btn"
                            data-target-type="comment"
                            data-target-id="${comment.id}"
                            data-type="dislike"
                            title="Дизлайкнути">
                        <i class="bi bi-hand-thumbs-down"></i>
                        <span class="dislike-count">${comment.dislikes_count}</span>
                    </button>
                </div>
                <button class="btn btn-sm btn-link reply-button" data-top-id="${comment.id}">
                    Відповісти
                </button>
                <div id="reply-form-container-${comment.id}"></div>
            </div>
        </div>
    `;

        return div;
    }


    function escapeHTML(str) {
        const div = document.createElement('div');
        div.innerText = str;
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('uk-UA') + ' ' + date.toLocaleTimeString('uk-UA', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    document.body.addEventListener('click', async e => {
        const btn = e.target.closest('.like-btn, .dislike-btn');
        if (!btn) return;

        const type = btn.dataset.type; // "like" або "dislike"
        const targetType = btn.dataset.targetType; // "thread" або "comment"
        const targetId = btn.dataset.targetId;

        try {
            const response = await apiFetch('/forum/like', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    user_id: <?= Core::getInstance()->session->get("user")['id'] ?>,
                    target_type: targetType,
                    target_id: targetId,
                    type: type,
                    add: 1,
                    created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
                })
            });

            // Оновити обидва лічильники, незалежно від натиснутої кнопки
            const container = btn.closest('.like-container'); // обгортка з обома кнопками
            if (container) {
                const likeSpan = container.querySelector('.like-btn .like-count');
                const dislikeSpan = container.querySelector('.dislike-btn .dislike-count');
                if (likeSpan) likeSpan.textContent = response.new_likes;
                if (dislikeSpan) dislikeSpan.textContent = response.new_dislikes;
            }

        } catch (err) {
            console.error('Помилка лайку:', err);
        }
    });


    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('comments-container');

        topComments.forEach(top => {
            const topEl = renderComment(top);
            container.appendChild(topEl);

            const children = childrenOfTop[top.id] || [];
            children.forEach(child => {
                const childEl = renderComment(child, 30);
                container.appendChild(childEl);
            });
        });
    });

    document.getElementById('comments-container').addEventListener('click', function(event) {
        const btn = event.target.closest('.reply-button');
        if (!btn) return;

        removeExistingReplyForms();

        const topId = btn.getAttribute('data-top-id');
        const container = document.getElementById('reply-form-container-' + topId);

        const formHtml = `
        <form class="reply-form mt-2" data-top-id="${topId}">
            <textarea rows="4"></textarea>
            <input type="hidden" name="thread_id" value="<?= $thread['id'] ?>">
            <input type="hidden" name="parent_id" value="${topId}"> 
            <input type="hidden" name="user_id" value="<?= Core::getInstance()->session->get("user")['id'] ?>">
            <input type="hidden" name="add" value="1">
            <button type="submit" class="btn btn-sm btn-primary mt-1">Відправити</button>
            <button type="button" class="btn btn-sm btn-secondary mt-1 cancel-reply">Скасувати</button>
        </form>
    `;
        container.innerHTML = formHtml;

        const textarea = container.querySelector('textarea');
        ClassicEditor
            .create(textarea, {
                extraPlugins: [CustomUploadPlugin],
                toolbar: ['bold', 'italic', 'link', 'bulletedList', 'numberedList', 'imageUpload', '|', 'undo', 'redo']
            })
            .then(editor => {
                container.querySelector('.reply-form').ckeditorInstance = editor;
            })
            .catch(error => console.error(error));

        container.querySelector('.cancel-reply').addEventListener('click', () => {
            const formEl = container.querySelector('.reply-form');
            const ed = formEl.ckeditorInstance;
            if (ed) {
                ed.destroy()
                    .then(() => {
                        container.innerHTML = '';
                    })
                    .catch(err => console.error(err));
            } else {
                container.innerHTML = '';
            }
        });

        container.querySelector('.reply-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const editorInst = form.ckeditorInstance;
            const contentData = editorInst.getData();

            const formData = new FormData();
            formData.append('content', contentData);
            formData.append('thread_id', form.querySelector('[name="thread_id"]').value);
            formData.append('parent_id', form.querySelector('[name="parent_id"]').value);
            formData.append('user_id', form.querySelector('[name="user_id"]').value);
            formData.append('add', form.querySelector('[name="add"]').value);

            try {
                const result = await apiFetch("/forum/<?= $thread['id'] ?>/comment?ajax=1&add=1", {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                if (result.edited) {
                    location.reload();
                } else {
                    showError('Помилка збереження відповіді.');
                }
            } catch (err) {
                console.error("AJAX error:", err);
                // showError уже викликаний всередині apiFetch → handleAjaxResponse
            }

        });
    });
</script>