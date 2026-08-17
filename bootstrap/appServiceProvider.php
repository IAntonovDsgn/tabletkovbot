<?php

use App\Application\Persistence\OutboxRepositoryInterface;
use App\Application\Persistence\UnitOfWorkInterface;
use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Infrastructure\Database\Dbal\Repository\IntakeMarkRepository;
use App\Infrastructure\Database\Dbal\Repository\MedicamentRepository;
use App\Infrastructure\Database\Dbal\Repository\OutboxRepository;
use App\Infrastructure\Database\Dbal\Repository\SessionRepository;
use App\Infrastructure\Database\Dbal\Repository\UnitOfWork;
use App\Infrastructure\TelegramMessageService\TelegramMessageService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Api;

use function DI\autowire;
use function DI\get;

return [

    /*==========================================
        Log
    ==========================================*/
    LoggerInterface::class => function () {
        $config = require __DIR__ . '/../config/logging.php';
        $path = $config['path'] ?? __DIR__ . '/../storage/logs/app.log';
        $channel = $config['channel'] ?? 'app';
        $monolog = new MonologLogger($channel);
        $handler = new StreamHandler($path, Level::Debug);
        $formatter = new LineFormatter(
            null,
            null,
            true,
            true
        );
        $handler->setFormatter($formatter);
        $monolog->pushHandler($handler);
        return $monolog;
    },

    'log' => \DI\get(LoggerInterface::class),

    /*==========================================
        Telegram
    ==========================================*/
    MessageServiceInterface::class => get(TelegramMessageService::class),
    TelegramMessageService::class => autowire(),
    Api::class => function () {
        $config = require __DIR__ . '/../config/telegram.php';
        return new Api($config['token']);
    },

    /*==========================================
        Database
     ==========================================*/
    Connection::class => function (ContainerInterface $c) {
        $config = require __DIR__ . '/../config/database.php';
        return DriverManager::getConnection($config);
    },

    UnitOfWorkInterface::class => autowire(UnitOfWork::class),
    OutboxRepositoryInterface::class => autowire(OutboxRepository::class),
    SessionRepositoryInterface::class => autowire(SessionRepository::class),
    IntakeMarkRepositoryInterface::class => autowire(IntakeMarkRepository::class),
    MedicamentRepositoryInterface::class => autowire(MedicamentRepository::class),
];
