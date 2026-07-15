<?php

namespace App\config;

return [
    'logging' => [
        'default' => 'single',
        'channels' => [
            'single' => [
                'driver' => 'single',
                'path' => __DIR__ . '/logs/app.log',
                'level' => 'debug',
            ],
        ],
    ],
];
