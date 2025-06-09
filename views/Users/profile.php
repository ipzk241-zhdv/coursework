<div class="container mt-5">
    <h2>Редагування профілю</h2>
    <form action="/users/profile" method="POST" enctype="multipart/form-data">

        <div class="row mb-3">
            <div class="col">
                <label for="name" class="form-label">Ім'я</label>
                <input type="text" class="form-control" id="name" name="name"
                    value="<?= htmlspecialchars(isset($user['name']) ? $user['name'] : '') ?>" required>
            </div>
            <div class="col">
                <label for="lastname" class="form-label">Прізвище</label>
                <input type="text" class="form-control" id="lastname" name="lastname"
                    value="<?= htmlspecialchars(isset($user['lastname']) ? $user['lastname'] : '') ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="patronymic" class="form-label">По батькові</label>
            <input type="text" class="form-control" id="patronymic" name="patronymic"
                value="<?= htmlspecialchars(isset($user['patronymic']) ? $user['patronymic'] : '') ?>">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email"
                value="<?= htmlspecialchars(isset($user['email']) ? $user['email'] : '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Новий пароль (залиште порожнім, якщо не змінюєте)</label>
            <input type="password" class="form-control" id="password" name="password">
        </div>

        <div class="mb-3">
            <label for="avatar" class="form-label">Аватар</label><br>
            <?php if (!empty($user['avatar'])): ?>
                <img src="/public/users/<?= htmlspecialchars($user['avatar']) ?>" alt="Поточний аватар"
                    class="img-thumbnail mb-2" style="max-width: 150px;">
            <?php else: ?>
                <div class="text-muted mb-2">Аватар не встановлено</div>
            <?php endif; ?>
            <input class="form-control" type="file" id="avatar" name="avatar" accept="image/*">
        </div>

        <div class="mb-3">
            <label for="date_of_birth" class="form-label">Дата народження</label>
            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                value="<?= htmlspecialchars(isset($user['date_of_birth']) ? $user['date_of_birth'] : '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="confirm_password" class="form-label text-danger">Підтвердіть поточний пароль</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn btn-primary">Оновити профіль</button>
    </form>
</div>