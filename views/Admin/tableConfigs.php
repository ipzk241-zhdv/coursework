<div class="container mt-4">
    <h2>Конфігурація таблиць</h2>

    <form id="configForm" class="row g-3 mb-4">
        <input type="hidden" name="id" id="config-id">
        <input type="hidden" name="put" value="1">

        <div class="col-md-3">
            <label for="table_name" class="form-label">Назва таблиці</label>
            <select class="form-select" id="table_name" name="table_name" required>
                <option value="">-- Виберіть таблицю --</option>
                <?php foreach ($tableNames as $name): ?>
                    <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label for="field" class="form-label">Поле</label>
            <input type="text" class="form-control" id="field" name="field" required>
        </div>

        <div class="col-md-3">
            <label for="label" class="form-label">Заголовок</label>
            <input type="text" class="form-control" id="label" name="label">
        </div>

        <div class="col-md-3">
            <label for="type" class="form-label">Тип</label>
            <select class="form-select" id="type" name="type">
                <option value="text">Текст</option>
                <option value="number">Число</option>
                <option value="date">Дата</option>
                <option value="image">Картинка</option>
                <option value="bool">Булеве</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Сортування</label>
            <input class="form-check-input" type="checkbox" id="sortable" name="sortable" value="1">
        </div>

        <div class="col-md-2">
            <label class="form-label">Пошук</label>
            <input class="form-check-input" type="checkbox" id="searchable" name="searchable" value="1">
        </div>

        <div class="col-md-2">
            <label class="form-label">Видимість</label>
            <input class="form-check-input" type="checkbox" id="visible" name="visible" value="1">
        </div>

        <div class="col-md-2">
            <label for="position" class="form-label">Позиція</label>
            <input type="number" class="form-control" id="position" name="position" value="0">
        </div>

        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100">Зберегти</button>
        </div>
    </form>

    <div class="col-md-4">
        <button type="button" class="btn btn-secondary w-100" onclick="autoGenerate()">Автозаповнення таблиці</button>
    </div>

    <table class="table table-bordered" id="configsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Таблиця</th>
                <th>Поле</th>
                <th>Заголовок</th>
                <th>Тип</th>
                <th>Сортується</th>
                <th>Пошук</th>
                <th>Видимість</th>
                <th>Позиція</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <!-- Записи завантажуються JS -->
        </tbody>
    </table>
</div>

<script src="/public/js/admin-tableConfigs.js"></script>