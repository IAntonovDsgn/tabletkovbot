<?php

return [
    'table_storage' => [
        'table_name' => 'migrations',
    ],
    'migrations_paths' => [
        'App\Infrastructure\Database\Dbal\Migrations' => __DIR__ . '/../src/Infrastructure/Database/Dbal/Migrations',
    ],
    'all_or_nothing' => true,
    'check_database_platform' => true,
];
