<?php

return [
    'dbname'   => $_ENV['MYSQL_DATABASE'] ?? 'tg_bot',
    'user'     => $_ENV['MYSQL_USER'] ?? 'root',
    'password' => $_ENV['MYSQL_PASSWORD'] ?? '',
    'host'     => $_ENV['MYSQL_HOST'] ?? 'db',
    'port'     => 3306,
    'driver'   => 'pdo_mysql',
    'charset'  => 'utf8mb4',
];
