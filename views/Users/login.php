<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="min-width: 320px; max-width: 400px; width: 100%;">
        <h3 class="card-title text-center mb-4">Увійти</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="Введіть email"
                    required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="Введіть пароль"
                    required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Увійти</button>
        </form>
    </div>
</div>