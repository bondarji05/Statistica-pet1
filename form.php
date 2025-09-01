<?php
// form.php
require __DIR__ . '/db.php'; // должен создать $pdo и session_start()

// Включи на время отладки, чтобы видеть реальную SQL-ошибку
const DEBUG_ERRORS = true;

$errors = [];

/** === Проверяем/добавляем колонку status в таблице profiles === */
try {
    // проверяем, что сама таблица есть
    $pdo->query("SELECT 1 FROM `profiles` LIMIT 1");

    // проверяем колонку status
    $col = $pdo->query("SHOW COLUMNS FROM `profiles` LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        $pdo->exec("
            ALTER TABLE `profiles`
            ADD COLUMN `status` ENUM('работает','уволен') NOT NULL DEFAULT 'работает' AFTER `department`
        ");
        // индекс — необязательно, но полезно
        $pdo->exec("CREATE INDEX idx_profiles_status ON `profiles`(`status`)");
    }
} catch (Throwable $e) {
    if (DEBUG_ERRORS) {
        echo '<div style="color:#b00;background:#fee;padding:8px;border:1px solid #fbb">
              Ошибка инициализации схемы: '
            . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</div>';
    }
}

/** Хелпер для «липких» значений */
function old($name, $default = '') {
    return htmlspecialchars($_POST[$name] ?? $default, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Проверка силы пароля */
function isStrongPassword(string $p): bool {
    if (strlen($p) < 8) return false;
    foreach (['/[a-z]/u','/[A-Z]/u','/\d/u','/[^\w\s]/u'] as $r) {
        if (!preg_match($r, $p)) return false;
    }
    return true;
}

// Подстановка email/пароля, если пришли с index.php
$prefillEmail = trim($_POST['email'] ?? '');
$prefillPass  = $_POST['password'] ?? '';

/** === Обработка формы === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__submit_form'])) {

    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $fio    = trim($_POST['fio'] ?? '');
    $dob    = trim($_POST['dob'] ?? '');           // YYYY-MM-DD
    $dept   = trim($_POST['department'] ?? '');
    $gender = $_POST['gender'] ?? '';              // male|female
    $statusForm = trim($_POST['status'] ?? '');    // working|dismissed

    // Валидация
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';
    if (!isStrongPassword($pass)) $errors[] = 'Пароль недостаточно надёжный (мин. 8 символов, строчная, заглавная, цифра, спецсимвол)';
    if ($fio === '')  $errors[] = 'Укажите ФИО';
    if ($dept === '') $errors[] = 'Укажите отдел';
    if ($dob === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) $errors[] = 'Укажите дату рождения (ГГГГ-ММ-ДД)';
    if (!in_array($gender, ['male','female'], true)) $errors[] = 'Выберите пол';
    if (!in_array($statusForm, ['working','dismissed'], true)) $errors[] = 'Укажите статус';

    // Проверка на существующий email
    if (!$errors) {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetchColumn()) {
            $errors[] = 'Пользователь с таким email уже существует. Пожалуйста, войдите на главной странице.';
        }
    }

    // Сохранение
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $st = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
            $st->execute([$email, $hash]);
            $userId = (int)$pdo->lastInsertId();

            // Маппинг статуса из формы -> как хранится в БД
            // Если хочешь хранить на английском — поменяй значения в ENUM и здесь на английские.
            $dbStatus = ($statusForm === 'working') ? 'работает' : 'уволен';

            $st = $pdo->prepare('
                INSERT INTO `profiles` (user_id, name, birth_date, gender, department, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $st->execute([$userId, $fio, $dob, $gender, $dept, $dbStatus]);

            $pdo->commit();

            // Автовход
            $_SESSION['user_id']    = $userId;
            $_SESSION['user_email'] = $email;

            header('Location: statistics.php');
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            if (DEBUG_ERRORS) {
                echo '<div style="color:#b00;background:#fee;padding:8px;border:1px solid #fbb">
                      SQL ошибка: '
                    . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</div>';
            }
            $errors[] = 'Ошибка при сохранении. Попробуйте ещё раз.';
        }
    }

    // вернуть значения в форму
    $prefillEmail = $email;
    $prefillPass  = '';
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Профиль</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-4">Профиль</h1>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="__submit_form" value="1">

                        <div class="mb-3">
                            <label for="email" class="form-label">Логин (email)</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?= old('email', $prefillEmail) ?>" required>
                            <div class="invalid-feedback">Укажите корректный email</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Минимум 8 символов: aA1!" required>
                            <div class="invalid-feedback">Введите надёжный пароль</div>
                        </div>

                        <div class="mb-3">
                            <label for="fio" class="form-label">ФИО</label>
                            <input type="text" id="fio" name="fio" class="form-control"
                                   value="<?= old('fio') ?>" required>
                            <div class="invalid-feedback">Укажите ФИО</div>
                        </div>

                        <div class="mb-3">
                            <label for="dob" class="form-label">Дата рождения</label>
                            <input type="date" id="dob" name="dob" class="form-control"
                                   value="<?= old('dob') ?>" required>
                            <div class="form-text">Формат: ГГГГ-ММ-ДД.</div>
                            <div class="invalid-feedback">Укажите дату рождения</div>
                        </div>

                        <div class="mb-3">
                            <label for="department" class="form-label">Отдел</label>
                            <input type="text" id="department" name="department" class="form-control"
                                   value="<?= old('department') ?>" required>
                            <div class="invalid-feedback">Укажите отдел</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Пол</label>
                            <?php $genderOld = $_POST['gender'] ?? ''; ?>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="g_male" name="gender" value="male"
                                    <?= $genderOld==='male' ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="g_male">Мужской</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="g_female" name="gender" value="female"
                                    <?= $genderOld==='female' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="g_female">Женский</label>
                            </div>

                            <div class="invalid-feedback d-block">Выберите пол</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Статус</label>
                            <?php $statusOld = $_POST['status'] ?? ''; ?>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="working" name="status" value="working"
                                    <?= $statusOld==='working' ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="working">Работает</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="dismissed" name="status" value="dismissed"
                                    <?= $statusOld==='dismissed' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="dismissed">Уволен</label>
                            </div>

                            <div class="invalid-feedback d-block">Укажите статус</div>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-success" type="submit">Завершить регистрацию</button>
                        </div>
                    </form>

                </div>
            </div>
            <p class="text-center mt-3">
                <a href="index.php" class="link-secondary">Вернуться на главную</a>
            </p>
        </div>
    </div>
</div>

<script>
    // Bootstrap validation
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
