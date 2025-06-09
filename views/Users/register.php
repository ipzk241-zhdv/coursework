<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="min-width: 320px; max-width: 450px; width: 100%;">
        <h3 class="card-title text-center mb-4">Реєстрація</h3>

        <!-- Блок помилок -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register">
            <div class="mb-3">
                <label for="name" class="form-label">Ім'я</label>
                <input
                    type="text"
                    class="form-control"
                    id="name"
                    name="name"
                    placeholder="Введіть ім'я"
                    required
                    minlength="2" maxlength="50"
                    value="<?= htmlspecialchars($name ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="lastName" class="form-label">Прізвище</label>
                <input
                    type="text"
                    class="form-control"
                    id="lastName"
                    name="lastName"
                    placeholder="Введіть прізвище"
                    required
                    minlength="2" maxlength="50"
                    value="<?= htmlspecialchars($lastName ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="patronymic" class="form-label">По батькові</label>
                <input
                    type="text"
                    class="form-control"
                    id="patronymic"
                    name="patronymic"
                    placeholder="Введіть по батькові"
                    maxlength="50"
                    value="<?= htmlspecialchars($patronymic ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="Введіть email"
                    required
                    value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="Введіть пароль"
                    required
                    minlength="6" maxlength="20">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Підтвердження пароля</label>
                <input
                    type="password"
                    class="form-control"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Повторіть пароль"
                    required
                    minlength="6" maxlength="20">
            </div>
            <button type="submit" class="btn btn-success w-100">Зареєструватися</button>
        </form>
    </div>
</div>