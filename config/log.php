<?php

namespace App\config;

return [
    'channel' => env('app'),
    'timezone' => env('LOG_CHANNEL_TIMEZONE', 'Asia/Yekaterinburg'),
    'handlers' => [
        [
            'type' => 'stream',
            'path' => env('LOG_CHANNEL_PATH', '../storage/logs/app.log'),
            'level' => 100,
        ],
    ]
];
