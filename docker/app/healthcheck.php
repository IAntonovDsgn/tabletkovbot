<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'db';
$name = getenv('DB_NAME') ?: 'tg_bot';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=3306;dbname=%s', $host, $name),
        $user,
        $pass,
        [
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ],
    );

    // Проверяем соединение с сервером
    $pdo->query('SELECT 1');

    exit(0);
} catch (Throwable) {
    exit(1);
}
