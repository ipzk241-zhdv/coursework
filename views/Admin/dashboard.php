<?php

use classes\Core;

/**
 * Доводимо всі вибірки до «масивів записів», навіть якщо з БД
 * прийшов один рядок або null.
 */
$normalize = function (&$var) {
    if (empty($var)) {
        $var = [];
        return;
    }
    if (!is_array($var)) {
        $var = [];
        return;
    }
    if (array_keys($var) !== range(0, count($var) - 1)) {
        // асоціативний одиночний запис → обгортаємо
        $var = [$var];
    }
};

foreach (
    [
        'statusStats',
        'errorStats',
        'userGrowth',
        'commentStats',
        'topCommentUsers',
        'blockOccupancy'
    ] as $key
) {
    $normalize($$key);
}

$dates = [];
$statusCodes = [];
$dataMap = [];

// Побудова мап
foreach ($statusStats as $row) {
    $date = $row['log_date'];
    $status = $row['status_code'];
    $count = $row['count'];

    if (!in_array($date, $dates)) $dates[] = $date;
    if (!in_array($status, $statusCodes)) $statusCodes[] = $status;

    $dataMap[$status][$date] = $count;
}

// Формування datasets
$datasets = [];
foreach ($statusCodes as $status) {
    $data = [];
    foreach ($dates as $date) {
        $data[] = $dataMap[$status][$date] ?? 0;
    }
    $datasets[] = [
        'label' => (string)$status,
        'data' => $data
    ];
}

?>

<!-- ⬇️ ГРАФІКИ / ТАБЛИЦІ ⬇️ -->

<div class="container py-4">
    <div class="row g-4">
        <!-- Графік 1: Статус-коди -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">📊 Статистика по статус-кодам</h5>
                    <?php if ($statusStats): ?>
                        <canvas id="statusChart"></canvas>
                    <?php else: ?>
                        <p>Даних поки що немає.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Графік 2: Нові користувачі -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">📈 Нові користувачі (30 днів)</h5>
                    <?php if ($userGrowth): ?>
                        <canvas id="userGrowthChart" height="150"></canvas>
                    <?php else: ?>
                        <p>Жодного нового користувача.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Графік 3: Коментарі -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">🗓️ Активність коментарів</h5>
                    <?php if ($commentStats): ?>
                        <canvas id="commentChart" height="150"></canvas>
                    <?php else: ?>
                        <p>Коментарів за останні дні не було.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Графік 4: Заповненість блоків -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">🏢 Заповненість блоків</h5>
                    <?php if ($blockOccupancy): ?>
                        <canvas id="blockChart" height="150"></canvas>
                    <?php else: ?>
                        <p>Немає інформації про блоки.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Додатково: список топ коментаторів -->
    <div class="mt-5">
        <h4>💬 Топ-10 коментаторів</h4>
        <?php if ($topCommentUsers): ?>
            <ul class="list-group">
                <?php foreach ($topCommentUsers as $u): ?>
                    <li class="list-group-item d-flex align-items-center">
                        <?= htmlspecialchars($u['name'] ?? '-') ?>
                        <span class="badge bg-primary rounded-pill mx-3"><?= (int)($u['comments'] ?? 0) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Немає даних</p>
        <?php endif; ?>
    </div>
</div>


<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    <?php if ($statusStats): ?>
        new Chart(document.getElementById('statusChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: <?= json_encode($datasets) ?>
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                stacked: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Статистика HTTP статус-кодів за останні 30 днів'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Кількість'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Дата'
                        }
                    }
                }
            }
        });
    <?php endif; ?>



    <?php if ($userGrowth): ?>
        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($userGrowth, 'day')) ?>,
                datasets: [{
                    label: 'Нові користувачі',
                    data: <?= json_encode(array_column($userGrowth, 'count')) ?>,
                    borderWidth: 2,
                    borderColor: '#2196f3',
                    fill: false,
                    tension: .3
                }]
            }
        });
    <?php endif; ?>

    <?php if ($commentStats): ?>
        new Chart(document.getElementById('commentChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($commentStats, 'day')) ?>,
                datasets: [{
                    label: 'К-сть коментарів',
                    data: <?= json_encode(array_column($commentStats, 'count')) ?>,
                    backgroundColor: '#4caf50'
                }]
            }
        });
    <?php endif; ?>

    <?php if ($blockOccupancy): ?>
        new Chart(document.getElementById('blockChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($blockOccupancy, 'block_code')) ?>,
                datasets: [{
                        label: 'Зайнято',
                        data: <?= json_encode(array_column($blockOccupancy, 'occupied_places')) ?>,
                        backgroundColor: '#f44336'
                    },
                    {
                        label: 'Вільно',
                        data: <?= json_encode(array_map(
                                    fn($b) => max((int)$b['total_places'] - (int)$b['occupied_places'], 0),
                                    $blockOccupancy
                                )) ?>,
                        backgroundColor: '#4caf50'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    <?php endif; ?>
</script>