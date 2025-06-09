<?php

use classes\Core;

date_default_timezone_set('Europe/Kyiv');
$today = date('m-d');
$birthdayUsers = Core::getInstance()->db->selectQuery("SELECT name, lastname, patronymic, avatar FROM users WHERE DATE_FORMAT(date_of_birth, '%m-%d') = :today", ["today" => $today]);
?>

<?php if (empty($birthdayUsers)): ?>
    <!-- Дні народження -->
    <section class="mb-5 mt-5"></section>
<?php else : ?>
    <!-- Дні народження -->
    <section class="mb-5 mt-5">
        <h2 class="mb-4 text-center">Сьогодні святкують День народження 🎉</h2>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($birthdayUsers as $user): ?>
                <div class="mb-2">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title d-flex align-items-center justify-content-center gap-2">
                                <img src="/public/users/<?= htmlspecialchars($user['avatar']) ?>" alt="user avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                <?= htmlspecialchars($user['name'] . " " . $user['lastname'] . " " . $user['patronymic']) ?>
                            </h5>
                            <p class="card-text">
                                Вітаємо з Днем народження! 🎂<br>
                                Бажаємо здоровʼя, щастя та успіхів!
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif ?>