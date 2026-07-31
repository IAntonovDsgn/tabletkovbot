<?php

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Infrastructure\Services\TelegramMessageService\TelegramMessageService;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Telegram\Bot\Api;
use function DI\autowire;
use function DI\get;

return [
     /*==========================================
                    ЛОГИРОВАНИЕ
     ==========================================*/
    LoggerInterface::class => function () {
        $config = require __DIR__.'/../config/logging.php';
        $path = $config['path'] ?? __DIR__.'/../storage/logs/app.log';
        $channel = $config['channel'] ?? 'app';

        $monolog = new MonologLogger($channel);
        $handler = new StreamHandler($path, Level::Debug);

        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);
        $monolog->pushHandler($handler);

        return $monolog;
    },

    'log' => \DI\get(LoggerInterface::class),

     /*==========================================
                     ТЕЛЕГРАМ
     ==========================================*/
    MessageServiceInterface::class => get(TelegramMessageService::class),

    TelegramMessageService::class => autowire()
        ->constructorParameter(
            'telegramConfig',
            require __DIR__ . '/../config/telegram.php'
        ),

    Api::class => function () {
        $config = require __DIR__ . '/../config/telegram.php';
        return new Api($config['token']);
    }
];
