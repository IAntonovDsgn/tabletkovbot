<?php

namespace App\Presentation\console;

use App\Presentation\console\Commands\GetTelegramUpdatesCommand;
use App\Presentation\console\Commands\SendTelegramMessageCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command as MigrationCommand;

$container = require __DIR__ . '/../../../bootstrap/bootstrap.php';

$commandLoader = new ContainerCommandLoader($container, [
    'app:tg-bot-get-updates'    => GetTelegramUpdatesCommand::class,
    'app:send-telegram-message' => SendTelegramMessageCommand::class,
]);

$application = new Application('app');
$application->setCommandLoader($commandLoader);

$connection = $container->get(Connection::class);

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
