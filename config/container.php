<?php

namespace App\config;

use App\Domain\Message\MessageFacadeInterface;
use App\Infrastructure\Services\TelegramMessage\TelegramMessageFacade;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Api;
use function DI\autowire;

return [
    'telegram.config' => function () {
        return require __DIR__ . '/telegram.php';
    },

    MessageFacadeInterface::class => autowire(TelegramMessageFacade::class),

    LoggerInterface::class => function () {
        $logger = new Logger('app');
        $logFile = $_ENV['LOG_FILE'] ?? __DIR__ . '/../storage/logs/app.log';
        $level = Level::fromName($_ENV['LOG_LEVEL'] ?? 'debug');
        $logger->pushHandler(new StreamHandler($logFile, $level));
        return $logger;
    },

    Api::class => function (ContainerInterface $c) {
        $config = $c->get('telegram.config');
        return new \Telegram\Bot\Api($config['token']);
    },
];
