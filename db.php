<?php
// db.php
$DB_HOST = 'localhost';
$DB_NAME = 'PET_1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_DSN  = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Ошибка подключения к БД.');
}

session_start();
