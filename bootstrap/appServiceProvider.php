<?php

use App\Application\Services\Outbox\ReportOutboxRepositoryInterface;
use App\Application\Services\DataSender\MessageBrokerInterface;
use App\Application\Services\DataSender\DataTransportInterface;
use App\Application\Services\Notification\NotificationService;
use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\Services\Outbox\OutboxRelay;
use App\Application\Services\DataSender\DataSender;
use App\Application\Services\PdfFactory\PdfFactoryInterface;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\UnitOfWorkInterface;
use App\Infrastructure\Dbal\Repositories\IntakeMarkRepository;
use App\Infrastructure\Dbal\Repositories\MedicamentRepository;
use App\Infrastructure\Dbal\Repositories\MessageOutboxRepository;
use App\Infrastructure\Dbal\Repositories\ReportOutboxRepository;
use App\Infrastructure\Dbal\Repositories\SessionRepository;
use App\Infrastructure\Dbal\UnitOfWork\UnitOfWork;
use App\Infrastructure\Logging\SizeLimitedFileHandler;
use App\Infrastructure\PDF\PdfFactory;
use App\Infrastructure\RabbitMq\AmqpConnectionFactory;
use App\Infrastructure\RabbitMq\AmqpConnectionFactoryInterface;
use App\Infrastructure\RabbitMq\Deserializer;
use App\Infrastructure\RabbitMq\MessageQueueConsumer;
use App\Infrastructure\RabbitMq\RabbitMqMessageBroker;
use App\Infrastructure\RabbitMq\ReportQueueConsumer;
use App\Presentation\Console\Commands\QueueConsumeCommand;
use App\Presentation\Console\Commands\ReportConsumeCommand;
use App\Infrastructure\RabbitMq\Serializer;
use App\Infrastructure\TelegramDataTransport\TelegramDataTransport;
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
        Logger
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

        $handler = new SizeLimitedFileHandler(
            $path,
            (int) ($config['max_bytes'] ?? 10 * 1024 * 1024),
            (int) ($config['max_backups'] ?? 5),
            Level::Debug,
        );
        $handler->setFormatter($formatterFactory());
        $monolog->pushHandler($handler);

        if ($config['stdout'] ?? false) {
            $stdoutHandler = new StreamHandler('php://stdout', Level::Debug);
            $stdoutHandler->setFormatter($formatterFactory());
            $monolog->pushHandler($stdoutHandler);
        }

        return $monolog;
    },

    'log' => get(LoggerInterface::class),

    /*==========================================
        Telegram
    ==========================================*/
    DataTransportInterface::class => get(TelegramDataTransport::class),
    TelegramDataTransport::class => autowire(),
    Api::class => function () {
        $config = require __DIR__ . '/../config/telegram.php';
        return new Api($config['token']);
    },

    /*==========================================
        Queue
    ==========================================*/
    MessageBrokerInterface::class => function (ContainerInterface $c) {
        $config = require __DIR__ . '/../config/rabbitmq.php';
        return new RabbitMqMessageBroker(
            new Serializer(),
            $c->get(LoggerInterface::class),
            (string) ($config['host'] ?? 'rabbitmq'),
            (int) ($config['port'] ?? 5672),
            (string) ($config['vhost'] ?? '/'),
            (string) ($config['user'] ?? 'guest'),
            (string) ($config['password'] ?? 'guest'),
            (string) ($config['exchange'] ?? 'outbox'),
            (string) ($config['message_queue'] ?? 'messages'),
            (string) ($config['report_queue'] ?? 'reports'),
            (float) ($config['confirm_timeout_seconds'] ?? 5.0),
        );
    },

    OutboxRelay::class => function (ContainerInterface $c) {
        return new OutboxRelay(
            $c->get(MessageOutboxRepositoryInterface::class),
            $c->get(ReportOutboxRepositoryInterface::class),
            $c->get(MessageBrokerInterface::class),
            $c->get(LoggerInterface::class),
            max(1, (int) ($_ENV['OUTBOX_BATCH_SIZE'] ?? 50)),
            max(1, (int) ($_ENV['OUTBOX_POLL_INTERVAL_MS'] ?? 1000)),
            max(1, (int) ($_ENV['OUTBOX_MAX_ATTEMPTS'] ?? 4)),
        );
    },

    NotificationService::class => function (ContainerInterface $c) {
        return new NotificationService(
            $c->get(MedicamentRepositoryInterface::class),
            $c->get(MessageOutboxRepositoryInterface::class),
            $c->get(UnitOfWorkInterface::class),
            $c->get(LoggerInterface::class),
            max(1, (int) ($_ENV['NOTIFY_POLL_INTERVAL_MS'] ?? 60000)),
        );
    },

    MessageQueueConsumer::class => function (ContainerInterface $c) {
        $config = require __DIR__ . '/../config/rabbitmq.php';
        $reportConfig = require __DIR__ . '/../config/report.php';
        return new MessageQueueConsumer(
            new Deserializer(),
            $c->get(LoggerInterface::class),
            (string) ($config['host'] ?? 'rabbitmq'),
            (int) ($config['port'] ?? 5672),
            (string) ($config['vhost'] ?? '/'),
            (string) ($config['user'] ?? 'guest'),
            (string) ($config['password'] ?? 'guest'),
            (string) ($config['exchange'] ?? 'outbox'),
            (string) ($config['message_queue'] ?? 'messages'),
            max(1, (int) ($_ENV['OUTBOX_POLL_INTERVAL_MS'] ?? 1000)),
            $c->get(DataSender::class),
            $c->get(MessageBrokerInterface::class),
            (int) ($reportConfig['max_send_attempts'] ?? 3),
            new AmqpConnectionFactory(),
        );
    },

    ReportQueueConsumer::class => function (ContainerInterface $c) {
        $config = require __DIR__ . '/../config/rabbitmq.php';
        $reportConfig = require __DIR__ . '/../config/report.php';
        return new ReportQueueConsumer(
            new Deserializer(),
            $c->get(LoggerInterface::class),
            (string) ($config['host'] ?? 'rabbitmq'),
            (int) ($config['port'] ?? 5672),
            (string) ($config['vhost'] ?? '/'),
            (string) ($config['user'] ?? 'guest'),
            (string) ($config['password'] ?? 'guest'),
            (string) ($config['exchange'] ?? 'outbox'),
            (string) ($config['report_queue'] ?? 'reports'),
            max(1, (int) ($_ENV['REPORT_POLL_INTERVAL_MS'] ?? 1000)),
            $c->get(DataSender::class),
            $c->get(MessageBrokerInterface::class),
            (int) ($reportConfig['max_send_attempts'] ?? 3),
            new AmqpConnectionFactory(),
        );
    },

    /*==========================================
        Queue consume commands

        Both commands depend on QueueConsumerInterface but need different concrete consumers,
        so autowiring cannot pick one for them — each command is wired explicitly.
     ==========================================*/
    QueueConsumeCommand::class => function (ContainerInterface $c) {
        return new QueueConsumeCommand($c->get(MessageQueueConsumer::class));
    },

    ReportConsumeCommand::class => function (ContainerInterface $c) {
        return new ReportConsumeCommand($c->get(ReportQueueConsumer::class));
    },

    AmqpConnectionFactoryInterface::class => autowire(AmqpConnectionFactory::class),

    /*==========================================
        PDF factory
     ==========================================*/
    PdfFactoryInterface::class => function (ContainerInterface $c) {
        $config = require __DIR__ . '/../config/report.php';
        return new PdfFactory(
            $c->get(IntakeMarkRepositoryInterface::class),
            $c->get(MedicamentRepositoryInterface::class),
            (int) ($config['max_days'] ?? 365),
            (int) ($config['temp_file_ttl'] ?? 3600),
        );
    },

    /*==========================================
        Database
     ==========================================*/
    Connection::class => function () {
        $config = require __DIR__ . '/../config/database.php';
        return DriverManager::getConnection($config);
    },

    UnitOfWorkInterface::class => autowire(UnitOfWork::class),
    MessageOutboxRepositoryInterface::class => autowire(MessageOutboxRepository::class),
    SessionRepositoryInterface::class => autowire(SessionRepository::class),
    IntakeMarkRepositoryInterface::class => autowire(IntakeMarkRepository::class),
    MedicamentRepositoryInterface::class => autowire(MedicamentRepository::class),
    ReportOutboxRepositoryInterface::class => autowire(ReportOutboxRepository::class)
];
