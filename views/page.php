<!-- header -->
<?php foreach ($modules['header'] as $moduleName): ?>
    <?php include __DIR__ . '/components/' . $moduleName; ?>
<?php endforeach; ?>

<!-- body -->
<div class="container">
    <?php foreach ($modules['body'] as $moduleName): ?>
        <?php include __DIR__ . '/components/' . $moduleName; ?>
    <?php endforeach; ?>
</div>

<!-- footer -->
<?php foreach ($modules['footer'] as $moduleName): ?>
    <?php include __DIR__ . '/components/' . $moduleName; ?>
<?php endforeach; ?>