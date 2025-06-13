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
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((result) => ({ default: result.url }))
                .catch((error) => Promise.reject(error.message || "Помилка завантаження"));
        });
    }
}

function CustomUploadPlugin(editor) {
    editor.plugins.get("FileRepository").createUploadAdapter = (loader) => new MyUploadAdapter(loader);
}

let threadEditor;

ClassicEditor.create(document.querySelector("#editor-root"), {
    extraPlugins: [CustomUploadPlugin],
    toolbar: ["heading", "|", "bold", "italic", "link", "bulletedList", "numberedList", "imageUpload", "|", "undo", "redo"],
})
    .then((editor) => {
        threadEditor = editor;
    })
    .catch(console.error);

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("thread-form");
    const userId = form.dataset.userId;
    const subcategoryId = form.dataset.subcategoryId;
    const subcategorySlug = form.dataset.subcategorySlug;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();
        form.querySelector('[name="content"]').value = threadEditor.getData();

        const formData = new FormData(form);
        formData.append("user_id", userId);
        formData.append("subcategory_id", subcategoryId);
        formData.append("add", 1);

        try {
            const response = await apiFetch("/forum/createthread", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: formData,
            });

            if (response.edited) {
                location.href = "/forum/" + subcategorySlug;
            }
        } catch (err) {
            console.error("Помилка відправки треду:", err);
        }
    });
});
