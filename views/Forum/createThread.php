<div class="container my-4">
    <h2>Створення нової теми</h2>

    <form id="thread-form" class="mt-4"
        data-subcategory-id="<?= $subcategory['id'] ?>"
        data-subcategory-slug="<?= $subcategory['slug'] ?>">

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
<script src="/public/js/createThread.js"></script>