<?php

use classes\Core;
use classes\Session;
use models\Users;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Hostel' ?> 🎃</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/haloween.css">
</head>



<body>
    <?php for ($i = 0; $i < 7; $i++): ?>
        <div class="<?= rand(0, 1) ? 'ghost' : 'pumpkin' ?>" style="left: <?= rand(0, 100) ?>vw; animation-delay: <?= rand(0, 10) ?>s;">
            <?= rand(0, 1) ? '👻' : '🎃' ?>
        </div>
    <?php endfor; ?>
    <header class="text-white sticky-top">
        <nav class="navbar navbar-expand-lg navbar-dark container">
            <a class="navbar-brand" href="/">🎃 Hostel</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Перемкнути навігацію">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Головна</a>
                    </li>
                    <?php if (Users::IsUserLogged()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/map/">Інтерактивна мапа</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/forum/">Форум</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/history/">Історія</a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php if (!Users::IsUserLogged()): ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-warning me-2" href="/users/login">Увійти</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-warning" href="/users/register">Реєстрація</a>
                        </li>
                    <?php else: ?>
                        <?php if (Core::getInstance()->session->get('user')['role'] === "admin"): ?>
                            <li class="nav-item">
                                <a class="btn btn-outline-warning me-2" href="/admin/dashboard">Адмін панель</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <img src="/public/users/<?= Core::getInstance()->session->get('user')['avatar'] ?? 'default.png' ?>" alt="Avatar" class="rounded-circle" width="30" height="30">
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <h6 class="dropdown-header">
                                        <?= Core::getInstance()->session->get('user')['name'] . " " . Core::getInstance()->session->get('user')['lastname'] . " " . Core::getInstance()->session->get('user')['patronymic'] ?>
                                    </h6>
                                </li>
                                <li><a class="dropdown-item" href="/users/profile">Налаштувати акаунт</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="/users/logout">Вийти</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>