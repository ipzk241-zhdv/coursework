<div class="container-fluid" data-selected="<?= htmlspecialchars($selected ?? '') ?>">
    <div class="row">
        <div class="col-md-3 border-end bg-light">
            <h4 class="mt-3">Компоненти</h4>
            <ul class="list-group" id="componentList">
                <?php foreach ($modules as $module): ?>
                    <li class="list-group-item list-group-item-action component-item" data-name="<?= $module["name"] ?>" data-exists="1" data-description="<?= $module["title"] ?>">
                        <?= $module["name"] ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5 class="mt-4">Не додані компоненти</h5>
            <ul class="list-group" id="notAddedComponentList">
                <?php foreach ($notAddedModules as $module): ?>
                    <li class="list-group-item list-group-item-action component-item" data-name="<?= $module ?>" data-exists="0">
                        <?= $module ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-md-9" id="previewPane">
            <div class="text-muted p-3">Оберіть компонент для перегляду.</div>
        </div>
    </div>
</div>