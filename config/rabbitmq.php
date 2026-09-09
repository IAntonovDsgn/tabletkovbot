<?php

return [
    'host' => $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
    'port' => (int) ($_ENV['RABBITMQ_PORT'] ?? 5672),
    'vhost' => $_ENV['RABBITMQ_VHOST'] ?? '/',
    'user' => $_ENV['RABBITMQ_USER'] ?? 'guest',
    'password' => $_ENV['RABBITMQ_PASSWORD'] ?? 'guest',
    'exchange' => 'outbox',
    'queue' => $_ENV['RABBITMQ_MESSAGE_QUEUE'] ?? 'telegram.send-message',
    'report_queue' => $_ENV['RABBITMQ_REPORT_QUEUE'] ?? 'report.generate',
    'confirm_timeout_seconds' => 5.0,
];
