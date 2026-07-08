<?php

namespace App\config;

return [
    'channel' => 'app',
    'handlers' => [
        [
            'type' => 'stream',
            'path' => __DIR__ . '/../storage/logs/app.log',
            'level' => 100,
        ],
    ]
];
