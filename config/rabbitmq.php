<?php

return [
    'host' => $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
    'port' => (int)($_ENV['RABBITMQ_PORT'] ?? 5672),
    'vhost' => $_ENV['RABBITMQ_VHOST'] ?? '/',
    'user' => $_ENV['RABBITMQ_USER'] ?? 'guest',
    'password' => $_ENV['RABBITMQ_PASSWORD'] ?? 'guest',
    'exchange' => 'outbox',
    'queue' => 'telegram.send-message',
    'confirm_timeout_seconds' => 5.0,
];
