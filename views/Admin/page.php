<style>
    .pagination-wrapper {
        overflow-x: auto;
        white-space: nowrap;
        padding: 0.5rem;
    }

    .pagination-wrapper ul.pagination {
        flex-wrap: nowrap;
    }
</style>

<div id="users-app" class="container my-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" id="search-input" class="form-control" placeholder="🔍 Пошук...">
        </div>
    </div>
    <div class="text-end my-2">
        <button id="add-btn" class="btn btn-primary" style="display: none;">➕ Додати запис</button>
    </div>
    <div class="text-end my-2">
        <button id="save-btn" class="btn btn-success" style="display: none;">💾 Зберегти зміни</button>
    </div>
    <div id="table-container" class="table-responsive">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Завантаження...</span>
            </div>
        </div>
    </div>
</div>

<script src="/public/js/ajax-error-handler.js"></script>
<script>
    const apiUrl = "<?= $apiUrl ?>";
    const configUrl = "<?= $configUrl ?>";
</script>
<script src="/public/js/admin-page.js"></script>