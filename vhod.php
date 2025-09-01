<?php
require __DIR__ . '/db.php';

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
    $_SESSION['flash'] = 'Заполните email и пароль.';
    header('Location: index.php'); exit;
}

$stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($pass, $user['password_hash'])) {
    // Успешный вход
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_email'] = $email;
    header('Location: http://localhost:8501'); // пока пустая
    exit;
}

// Неуспешно — предлагается зарегистрироваться
$_SESSION['flash'] = 'Неверный логин или пароль. Можете зарегистрироваться.';
header('Location: index.php');
