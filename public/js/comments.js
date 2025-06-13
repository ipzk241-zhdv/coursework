class MyUploadAdapter {
    constructor(loader) {
        this.loader = loader;
    }

    upload() {
        return this.loader.file.then((file) => {
            const data = new FormData();
            data.append("upload", file);

            return apiFetch("/forum/commentImage", {
                method: "POST",
                body: data,
                headers: { "X-Requested-With": "XMLHttpRequest" },
            })
                .then((result) => ({ default: result.url }))
                .catch((error) => Promise.reject(error.message || "Помилка завантаження"));
        });
    }
}

function CustomUploadPlugin(editor) {
    editor.plugins.get("FileRepository").createUploadAdapter = (loader) => new MyUploadAdapter(loader);
}

let rootEditor;
ClassicEditor.create(document.querySelector("#editor-root"), {
    extraPlugins: [CustomUploadPlugin],
    toolbar: ["heading", "|", "bold", "italic", "link", "bulletedList", "numberedList", "imageUpload", "|", "undo", "redo"],
})
    .then((editor) => {
        rootEditor = editor;
    })
    .catch(console.error);

document.getElementById("comment-form").addEventListener("submit", async function (e) {
    e.preventDefault();
    const form = e.target;
    form.querySelector('[name="content"]').value = rootEditor.getData();

    const formData = new FormData(form);
    try {
        const response = await apiFetch(COMMENT_POST_URL, {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: formData,
        });
        if (response.edited) location.reload();
    } catch (err) {
        console.error("Помилка запиту:", err);
    }
});

function removeExistingReplyForms() {
    document.querySelectorAll(".reply-form").forEach((formEl) => {
        const ed = formEl.ckeditorInstance;
        if (ed) ed.destroy().catch(console.error);
        formEl.parentElement.innerHTML = "";
    });
}

document.querySelectorAll(".reply-button").forEach((button) => {
    button.addEventListener("click", function () {
        removeExistingReplyForms();

        const topId = this.getAttribute("data-top-id");
        const container = document.getElementById("reply-form-container-" + topId);

        const formHtml = `
                <form class="reply-form mt-2" data-top-id="${topId}">
                    <textarea rows="4"></textarea>
                    <input type="hidden" name="thread_id" value="${FORUM_THREAD_ID}">
                    <input type="hidden" name="parent_id" value="${topId}">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Відправити</button>
                    <button type="button" class="btn btn-sm btn-secondary mt-1 cancel-reply">Скасувати</button>
                </form>
            `;
        container.innerHTML = formHtml;

        const textarea = container.querySelector("textarea");
        ClassicEditor.create(textarea, {
            extraPlugins: [CustomUploadPlugin],
            toolbar: ["bold", "italic", "link", "bulletedList", "numberedList", "imageUpload", "|", "undo", "redo"],
        })
            .then((editor) => {
                container.querySelector(".reply-form").ckeditorInstance = editor;
            })
            .catch(console.error);

        container.querySelector(".cancel-reply").addEventListener("click", () => {
            const formEl = container.querySelector(".reply-form");
            const ed = formEl.ckeditorInstance;
            if (ed) {
                ed.destroy()
                    .then(() => {
                        container.innerHTML = "";
                    })
                    .catch(console.error);
            } else {
                container.innerHTML = "";
            }
        });

        container.querySelector(".reply-form").addEventListener("submit", async function (e) {
            e.preventDefault();
            const form = e.target;
            const editorInst = form.ckeditorInstance;
            const contentData = editorInst.getData();

            const formData = new FormData(form);
            formData.set("content", contentData);

            try {
                const result = await apiFetch(COMMENT_POST_URL, {
                    method: "POST",
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                    body: formData,
                });
                if (result.edited) {
                    location.reload();
                } else {
                    showError("Коментар не було збережено.");
                }
            } catch (err) {
                console.error("AJAX error:", err);
            }
        });
    });
});
