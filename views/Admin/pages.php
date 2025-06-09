<div class="container-fluid">
    <h2>Редагування модулів сторінки</h2>

    <div class="row">
        <!-- Список всіх модулів -->
        <div class="col-md-3">
            <h4 class="mt-3">Компоненти</h4>
            <ul class="list-group" id="componentList">
                <?php foreach ($modules as $index => $module): ?>
                    <li class="list-group-item list-group-item-action component-item"
                        draggable="true"
                        data-id="<?= $module['id'] ?>"
                        title="<?= $module['title'] ?>"
                        data-name="<?= htmlspecialchars($module['name']) ?>">
                        <?= htmlspecialchars($module['name']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Редагування секцій -->
        <div class="col-md-9">
            <div class="mb-3">
                <label for="pageSelect" class="form-label">Виберіть сторінку:</label>
                <select class="form-select" id="pageSelect">
                    <?php foreach ($pages as $index => $pageName): ?>
                        <option value="<?= $index ?>"><?= htmlspecialchars($pageName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <!-- HEADER -->
                <div class="col-12 mb-3">
                    <div class="card">
                        <div class="card-header">Header</div>
                        <ul class="list-group list-group-flush drop-zone" data-section="header" id="headerZone">
                            <?php foreach ($pageModules['header'] as $moduleName): ?>
                                <li class="list-group-item module-item"
                                    draggable="true"
                                    data-id="<?= array_search($moduleName, $modules) ?>"
                                    data-name="<?= htmlspecialchars($moduleName) ?>">
                                    <span><?= htmlspecialchars($moduleName) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- BODY -->
                <div class="col-12 mb-3">
                    <div class="card">
                        <div class="card-header">Body</div>
                        <ul class="list-group list-group-flush drop-zone" data-section="body" id="bodyZone">
                            <?php foreach ($pageModules['body'] as $moduleName): ?>
                                <li class="list-group-item module-item"
                                    draggable="true"
                                    data-id="<?= array_search($moduleName, $modules) ?>"
                                    data-name="<?= htmlspecialchars($moduleName) ?>">
                                    <span><?= htmlspecialchars($moduleName) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="col-12 mb-3">
                    <div class="card">
                        <div class="card-header">Footer</div>
                        <ul class="list-group list-group-flush drop-zone" data-section="footer" id="footerZone">
                            <?php foreach ($pageModules['footer'] as $moduleName): ?>
                                <li class="list-group-item module-item"
                                    draggable="true"
                                    data-id="<?= array_search($moduleName, $modules) ?>"
                                    data-name="<?= htmlspecialchars($moduleName) ?>">
                                    <span><?= htmlspecialchars($moduleName) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="deleteIndicator" class="delete-indicator">
                Відпустіть поза секцій, щоб <strong>видалити</strong> модуль
            </div>

            <button class="btn btn-success" id="saveChanges">💾 Зберегти зміни</button>
        </div>
    </div>
</div>

<hr class="mt-5">
<h4>🔍 Передперегляд сторінки</h4>
<div id="pagePreview">
    <div id="pagePreview">
        <div id="previewHeader">
            <div class="loading-indicator">Завантаження...</div>
        </div>
        <div class="container" id="previewBody">
            <div class="loading-indicator">Завантаження...</div>
        </div>
        <div id="previewFooter">
            <div class="loading-indicator">Завантаження...</div>
        </div>
    </div>
</div>


<script src="/public/js/admin-constructor.js"></script>