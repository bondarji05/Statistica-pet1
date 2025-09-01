<?php
require __DIR__ . '/db.php';

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
    $_SESSION['flash'] = 'Укажите email и пароль для регистрации.';
    header('Location: index.php'); exit;
}

// Есть ли уже такой пользователь?
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
$exists = $stmt->fetchColumn();

if ($exists) {
    $_SESSION['flash'] = 'Пользователь уже существует. Пожалуйста, войдите.';
    header('Location: index.php'); exit;
}

// Нет пользователя — отправляем на анкету.
// Пароль через GET не передаём; положим временно в сессию.
$_SESSION['pre_reg_email'] = $email;
$_SESSION['pre_reg_pass']  = $pass;

header('Location: form.php');
