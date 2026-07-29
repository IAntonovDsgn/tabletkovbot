<?php

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Infrastructure\Services\TelegramMessageService\TelegramMessageService;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Api;
use function DI\autowire;
use function DI\get;

return [
    'telegram.config' => function () {
        return require __DIR__ . '/telegram.php';
    },

    MessageServiceInterface::class => get(TelegramMessageService::class),

    TelegramMessageService::class => autowire()
        ->constructorParameter('telegramConfig', get('telegram.config')),

    LoggerInterface::class => function () {
        $logger = new Logger('app');
        $logFile = $_ENV['LOG_FILE'] ?? __DIR__ . '/../storage/logs/app.log';
        $level = Level::fromName($_ENV['LOG_LEVEL'] ?? 'debug');
        $logger->pushHandler(new StreamHandler($logFile, $level));
        return $logger;
    },

    Api::class => function (ContainerInterface $c) {
        $config = $c->get('telegram.config');
        return new Api($config['token']);
    },
];
