<?php

namespace App\config;

use App\Domain\Message\MessageFacadeInterface;
use App\Infrastructure\Services\Message\MessageFacade;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

use function DI\autowire;

return [
    MessageFacadeInterface::class => autowire(MessageFacade::class),

    'telegram.config' => function () {
        return require __DIR__ . '/telegram.php';
    },

    LoggerInterface::class => function () {
        $logger = new Logger('app');
        $logFile = $_ENV['LOG_FILE'] ?? __DIR__ . '/../storage/logs/app.log';
        $level = Level::fromName($_ENV['LOG_LEVEL'] ?? 'debug');
        $logger->pushHandler(new StreamHandler($logFile, $level));
        return $logger;
    },
];
