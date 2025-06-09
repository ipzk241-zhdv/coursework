<?php
    use classes\Core;

    $db = Core::getInstance()->db;
    $countRooms = $db->count('rooms', "room_type = 'кімната'");
    $countKitchens = $db->count('rooms', "room_type = 'кухня'");
    $countLaundry = $db->count('rooms', "room_type = 'пральня'");
    $countPlaces = $db->selectQuery("SELECT SUM(places) AS total_places FROM rooms")[0]['total_places'];
    $countResidents = $db->count('users', 'room_id is not null');
?>

<!-- В цифрах  -->
    <section class="container py-5 mt-5">
        <h2 class="text-center mb-5">Гуртожиток в цифрах</h2>

        <div class="row gy-2 gx-4">
            <!-- Ліва частина -->
            <div class="col-12 col-md-6">
                <h5 class="text-center mb-3">Інфраструктура</h5>
                <div class="row g-3 d-flex justify-content-center flex-wrap">
                    <div class="col-12 col-sm-6 col-lg-5">
                        <div class="card text-center shadow-sm">
                            <div class="card-body card-shadow d-inline-block">
                                <h6 class="stat-title">Кімнат</h6>
                                <p class="stat-number"><?= $countRooms ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-5">
                        <div class="card text-center shadow-sm">
                            <div class="card-body card-shadow d-inline-block">
                                <h6 class="stat-title">Поверхів</h6>
                                <p class="stat-number">5</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-5">
                        <div class="card text-center shadow-sm">
                            <div class="card-body card-shadow d-inline-block">
                                <h6 class="stat-title">Кухонь</h6>
                                <p class="stat-number"><?= $countKitchens ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-5">
                        <div class="card text-center shadow-sm">
                            <div class="card-body card-shadow d-inline-block">
                                <h6 class="stat-title">Пральні</h6>
                                <p class="stat-number"><?= $countLaundry ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Права частина -->
            <div class="col-12 col-md-6">
                <h5 class="text-center mb-3">Статистика проживання</h5>
                <div class="row g-3 d-flex justify-content-center flex-wrap">
                    <div class="col-12 col-sm-6 col-lg-5">
                        <div class="card text-center shadow-sm">
                            <div class="card-body card-shadow d-inline-block">
                                <h6 class="stat-title">Місць</h6>
                                <p class="stat-number"><?= $countPlaces ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-5">
                        <div class="card text-center shadow-sm">
                            <div class="card-body card-shadow d-inline-block">
                                <h6 class="stat-title">Заселених</h6>
                                <p class="stat-number"><?= $countResidents ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-5">
                        <div class="card text-center shadow-sm">
                            <div class="card-body card-shadow d-inline-block">
                                <h6 class="stat-title">Середня оцінка</h6>
                                <p class="stat-number">4.88</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-5">
                        <div class="card text-center shadow-sm">
                            <div class="card-body card-shadow d-inline-block">
                                <h6 class="stat-title">Вільних місць</h6>
                                <p class="stat-number"><?= $countPlaces - $countResidents ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>