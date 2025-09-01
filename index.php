<?php require __DIR__ . '/db.php'; ?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Форма регистрации / вход</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-4 text-center">Форма регистрации</h1>

                    <?php if (!empty($_SESSION['flash'])): ?>
                        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Введите логин (email)</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="name@example.com">
                            <div class="invalid-feedback">Укажите корректный email.</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Введите пароль</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="invalid-feedback">Введите пароль.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" type="submit" formaction="vhod.php">Вход</button>
                            <!-- Идём сразу на форму регистрации; если поля пустые — ОК -->
                            <button class="btn btn-outline-secondary" type="submit" formaction="form.php">Регистрация</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>
</body>
</html>
