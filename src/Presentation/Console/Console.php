<?php

declare(strict_types=1);

namespace App\Presentation\Console;

use App\Presentation\Console\Commands\GetTelegramUpdatesCommand;
use App\Presentation\Console\Commands\NotifyCommand;
use App\Presentation\Console\Commands\OutboxPublishCommand;
use App\Presentation\Console\Commands\MessageQueueConsumeCommand;
use App\Presentation\Console\Commands\ReportConsumeCommand;
use App\Presentation\Console\Commands\SendTelegramMessageCommand;
use DI\Container;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command as MigrationCommand;

$container = require __DIR__ . '/../../../bootstrap/bootstrap.php';
if (!$container instanceof Container) {
    throw new RuntimeException('bootstrap/bootstrap.php must return a DI\Container instance.');
}

$commandLoader = new ContainerCommandLoader($container, [
    'app:tg-bot-get-updates'    => GetTelegramUpdatesCommand::class,
    'app:send-telegram-message' => SendTelegramMessageCommand::class,
    'app:outbox-publish'        => OutboxPublishCommand::class,
    'app:message-queue-consume'         => MessageQueueConsumeCommand::class,
    'app:report-queue-consume'        => ReportConsumeCommand::class,
    'app:medication-notify'     => NotifyCommand::class,
]);

$application = new Application('app');
$application->setCommandLoader($commandLoader);

$connection = $container->get(Connection::class);
if (!$connection instanceof Connection) {
    throw new RuntimeException(sprintf('Container entry "%s" must be a DBAL Connection.', Connection::class));
}

$config = new PhpFile(__DIR__ . '/../../../config/migrations.php');
$dependencyFactory = DependencyFactory::fromConnection($config, new ExistingConnection($connection));

$application->addCommands([
    new MigrationCommand\GenerateCommand($dependencyFactory),
    new MigrationCommand\MigrateCommand($dependencyFactory),
    new MigrationCommand\StatusCommand($dependencyFactory),
    new MigrationCommand\ExecuteCommand($dependencyFactory),
    new MigrationCommand\LatestCommand($dependencyFactory),
    new MigrationCommand\UpToDateCommand($dependencyFactory),
    new MigrationCommand\VersionCommand($dependencyFactory),
]);

$application->run();
