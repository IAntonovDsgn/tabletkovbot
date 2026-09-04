<?php

use App\Application\Message\MessageBrokerInterface;
use App\Application\Message\MessageServiceInterface;
use App\Application\Message\QueueConsumerInterface;
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
use App\Infrastructure\Database\Dbal\UnitOfWork\UnitOfWork;
use App\Infrastructure\RabbitMq\AmqpConnectionFactory;
use App\Infrastructure\RabbitMq\AmqpConnectionFactoryInterface;
use App\Infrastructure\RabbitMq\MessagePayloadDeserializer;
use App\Infrastructure\RabbitMq\MessagePayloadSerializer;
use App\Infrastructure\RabbitMq\RabbitMqMessageBroker;
use App\Infrastructure\RabbitMq\RabbitMqQueueConsumer;
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
        $path = (string) ($config['path'] ?? __DIR__ . '/../storage/logs/app.log');
        $channel = (string) ($config['channel'] ?? 'app');
        $formatterFactory = function () {
            return new LineFormatter(
                null,
                null,
                true,
                true
            );
        };

        $monolog = new MonologLogger($channel);

        $handler = new StreamHandler($path, Level::Debug);
        $handler->setFormatter($formatterFactory());
        $monolog->pushHandler($handler);

        if ($config['stdout'] ?? false) {
            $stdoutHandler = new StreamHandler('php://stdout', Level::Debug);
            $stdoutHandler->setFormatter($formatterFactory());
            $monolog->pushHandler($stdoutHandler);
        }

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
    MessageBrokerInterface::class => function (ContainerInterface $c) {
        $config = require __DIR__ . '/../config/rabbitmq.php';
        return new RabbitMqMessageBroker(
            new MessagePayloadSerializer(),
            $c->get(LoggerInterface::class),
            (string) ($config['host'] ?? 'rabbitmq'),
            (int) ($config['port'] ?? 5672),
            (string) ($config['vhost'] ?? '/'),
            (string) ($config['user'] ?? 'guest'),
            (string) ($config['password'] ?? 'guest'),
            (string) ($config['exchange'] ?? 'outbox'),
            (string) ($config['queue'] ?? 'telegram.send-message'),
            (float) ($config['confirm_timeout_seconds'] ?? 5.0),
        );
    },

    QueueConsumerInterface::class => function (ContainerInterface $c) {
        $config = require __DIR__ . '/../config/rabbitmq.php';
        return new RabbitMqQueueConsumer(
            new MessagePayloadDeserializer(),
            $c->get(LoggerInterface::class),
            (string) ($config['host'] ?? 'rabbitmq'),
            (int) ($config['port'] ?? 5672),
            (string) ($config['vhost'] ?? '/'),
            (string) ($config['user'] ?? 'guest'),
            (string) ($config['password'] ?? 'guest'),
            (string) ($config['exchange'] ?? 'outbox'),
            (string) ($config['queue'] ?? 'telegram.send-message'),
            max(1, (int) ($_ENV['OUTBOX_POLL_INTERVAL_MS'] ?? 1000)),
        );
    },

    OutboxRelay::class => function (ContainerInterface $c) {
        return new OutboxRelay(
            $c->get(OutboxRepositoryInterface::class),
            $c->get(MessageBrokerInterface::class),
            max(1, (int) ($_ENV['OUTBOX_BATCH_SIZE'] ?? 50)),
            max(1, (int) ($_ENV['OUTBOX_POLL_INTERVAL_MS'] ?? 1000)),
            max(1, (int) ($_ENV['OUTBOX_MAX_ATTEMPTS'] ?? 4)),
        );
    },

    AmqpConnectionFactoryInterface::class => autowire(AmqpConnectionFactory::class),

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
