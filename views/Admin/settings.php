<div class="container mt-4">
    <h1>Налаштування</h1>
    <form id="settingsForm">
        <div class="mb-3">
            <label for="currentLayoutId" class="form-label">Поточний Layout</label>
            <select class="form-select" id="currentLayoutId" name="current_layout_id" required></select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="toCache" name="to_cache" />
            <label class="form-check-label" for="toCache">Використовувати кеш</label>
        </div>

        <div class="mb-3">
            <label for="cacheLifetime" class="form-label">Тривалість кешу (секунди)</label>
            <input type="number" class="form-control" id="cacheLifetime" name="cache_lifetime" min="0" />
        </div>

        <div class="mb-3">
            <label for="excludeCache" class="form-label">Методи, виключені з кешування</label>
            <textarea class="form-control" id="excludeCache" name="exclude_cache" rows="3" placeholder="Наприклад: ['method1', 'method2']"></textarea>
            <div class="form-text">Введіть масив методів</div>
        </div>

        <button type="submit" class="btn btn-primary">Зберегти налаштування</button>
    </form>
</div>

<script src="/public/js/ajax-error-handler.js"></script>
<script src="/public/js/settings.js"></script>
