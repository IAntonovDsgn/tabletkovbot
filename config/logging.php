<?php

return [
    'path' => __DIR__ . '/../storage/logs/app.log',
    'channel' => 'app',
    'stdout' => filter_var($_ENV['LOG_STDOUT'] ?? true, FILTER_VALIDATE_BOOL),
    'max_bytes' => (int) ($_ENV['LOG_MAX_BYTES'] ?? 10 * 1024 * 1024),
    'max_backups' => (int) ($_ENV['LOG_MAX_BACKUPS'] ?? 5),
];
