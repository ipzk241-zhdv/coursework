<div class="container my-4">
    <h2>Створення нової теми</h2>

    <form id="thread-form" class="mt-4">
        <div class="mb-3">
            <label for="thread-title" class="form-label">Заголовок теми</label>
            <input type="text" name="title" id="thread-title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="editor-root" class="form-label">Вміст</label>
            <textarea name="content" id="editor-root" rows="8" class="form-control"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Створити тему</button>
    </form>
</div>

<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
<script src="/public/js/ajax-error-handler.js"></script>

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
                    .catch(error => Promise.reject(error.message || 'Помилка завантаження'));
            });
        }
    }

    function CustomUploadPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = loader => new MyUploadAdapter(loader);
    }

    let threadEditor;

    ClassicEditor
        .create(document.querySelector('#editor-root'), {
            extraPlugins: [CustomUploadPlugin],
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'imageUpload', '|', 'undo', 'redo']
        })
        .then(editor => {
            threadEditor = editor;
        })
        .catch(console.error);

    document.getElementById('thread-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        form.querySelector('[name="content"]').value = threadEditor.getData();

        const formData = new FormData(form);

        formData.append("user_id", <?= $userid ?>);
        formData.append("subcategory_id", <?= $subcategory['id'] ?>);
        formData.append("add", 1);

        try {
            const response = await apiFetch("/forum/createthread", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData
            });

            console.log(response.edited);
            if (response.edited) {
                slug = '<?= $subcategory['slug'] ?>';
                location.href = '/forum/' + slug;
            }
        } catch (err) {
            console.error('Помилка відправки треду:', err);
        }
    });
</script>