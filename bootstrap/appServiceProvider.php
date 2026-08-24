<?php

use App\Application\Message\MessageBrokerInterface;
use App\Application\Message\MessageServiceInterface;
use App\Application\Outbox\OutboxRelay;
use App\Application\Outbox\OutboxRepositoryInterface;
use App\Application\UnitOfWork\UnitOfWorkInterface;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Infrastructure\Database\Dbal\Repositories\IntakeMarkRepository;
use App\Infrastructure\Database\Dbal\Repositories\MedicamentRepository;
use App\Infrastructure\Database\Dbal\Repositories\OutboxRepository;
use App\Infrastructure\Database\Dbal\Repositories\SessionRepository;
use App\Infrastructure\Database\Dbal\Repositories\UnitOfWork;
use App\Infrastructure\RabbitMq\MessagePayloadSerializer;
use App\Infrastructure\RabbitMq\RabbitMqMessageBroker;
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
        Message broker (RabbitMQ)
    ==========================================*/
    MessageBrokerInterface::class => function () {
        $config = require __DIR__ . '/../config/rabbitmq.php';
        return new RabbitMqMessageBroker(
            new MessagePayloadSerializer(),
            (string)($config['host'] ?? 'rabbitmq'),
            (int)($config['port'] ?? 5672),
            (string)($config['vhost'] ?? '/'),
            (string)($config['user'] ?? 'guest'),
            (string)($config['password'] ?? 'guest'),
            (string)($config['exchange'] ?? 'outbox'),
            (string)($config['queue'] ?? 'telegram.send-message'),
            (float)($config['confirm_timeout_seconds'] ?? 5.0),
        );
    },

    OutboxRelay::class => function (ContainerInterface $c) {
        return new OutboxRelay(
            $c->get(OutboxRepositoryInterface::class),
            $c->get(MessageBrokerInterface::class),
            $c->get(LoggerInterface::class),
            max(1, (int)($_ENV['OUTBOX_BATCH_SIZE'] ?? 50)),
            max(1, (int)($_ENV['OUTBOX_POLL_INTERVAL_MS'] ?? 1000)),
        );
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
