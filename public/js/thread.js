const { threadId, topComments, childrenOfTop } = window.FORUM_CONFIG;

function escapeHTML(str) {
    const div = document.createElement("div");
    div.innerText = str;
    return div.innerHTML;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return (
        date.toLocaleDateString("uk-UA") +
        " " +
        date.toLocaleTimeString("uk-UA", {
            hour: "2-digit",
            minute: "2-digit",
        })
    );
}

function renderComment(comment, indent = 0) {
    const div = document.createElement("div");
    div.className = "border rounded p-3 mb-2";
    div.style.marginLeft = indent + "px";

    const avatarUrl = comment.author_avatar ? `/public/users/${comment.author_avatar}` : `/public/users/default.png`;

    div.innerHTML = `
        <div style="display: flex; gap: 15px;">
            <div style="flex-shrink: 0; text-align: center; width: 80px;">
                <img src="${avatarUrl}" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                <div class="fw-bold mt-2" style="font-size: 0.9rem;">${escapeHTML(comment.author_name)}</div>
            </div>
            <div style="flex-grow: 1;">
                <div class="text-muted small mt-2">Опубліковано ${formatDate(comment.created_at)}</div>
                <div class="mt-4" style="white-space: pre-wrap;">${comment.content}</div>
                <hr>
                <div class="d-flex align-items-center like-container">
                    <button type="button" class="btn btn-outline-primary btn-sm me-2 like-btn" data-target-type="comment" data-target-id="${
                        comment.id
                    }" data-type="like" title="Лайкнути">
                        <i class="bi bi-hand-thumbs-up"></i> <span class="like-count">${comment.likes_count}</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm dislike-btn" data-target-type="comment" data-target-id="${
                        comment.id
                    }" data-type="dislike" title="Дизлайкнути">
                        <i class="bi bi-hand-thumbs-down"></i> <span class="dislike-count">${comment.dislikes_count}</span>
                    </button>
                </div>
                <button class="btn btn-sm btn-link reply-button" data-top-id="${comment.id}">Відповісти</button>
                <div id="reply-form-container-${comment.id}"></div>
            </div>
        </div>
    `;
    return div;
}

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("comments-container");

    topComments.forEach((top) => {
        const topEl = renderComment(top);
        container.appendChild(topEl);

        const children = childrenOfTop[top.id] || [];
        children.forEach((child) => {
            const childEl = renderComment(child, 30);
            container.appendChild(childEl);
        });
    });
});

document.body.addEventListener("click", async (e) => {
    const btn = e.target.closest(".like-btn, .dislike-btn");
    if (!btn) return;

    const { type, targetType, targetId } = btn.dataset;

    try {
        const response = await apiFetch("/forum/like", {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: new URLSearchParams({
                target_type: targetType,
                target_id: targetId,
                type,
                add: 1,
                created_at: new Date().toISOString().slice(0, 19).replace("T", " "),
            }),
        });

        const container = btn.closest(".like-container");
        if (container) {
            container.querySelector(".like-btn .like-count").textContent = response.new_likes;
            container.querySelector(".dislike-btn .dislike-count").textContent = response.new_dislikes;
        }
    } catch (err) {
        console.error("Помилка лайку:", err);
    }
});
