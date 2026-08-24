<?php

return [
    'path' => __DIR__ . '/../storage/logs/app.log',
    'channel' => 'app',
    'stdout' => filter_var($_ENV['LOG_STDOUT'] ?? true, FILTER_VALIDATE_BOOL),
];
