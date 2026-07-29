<?php

namespace Presentation\console;

use App\Presentation\console\Commands\GetTelegramUpdatesCommand;
use App\Presentation\console\Commands\SendTelegramMessageCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;


$container = require __DIR__ . '/../../../bootstrap/bootstrap.php';

$commandLoader = new ContainerCommandLoader($container, [
    'app:tg-bot-get-updates'    => GetTelegramUpdatesCommand::class,
    'app:send-telegram-message' => SendTelegramMessageCommand::class,
]);

$application = new Application('app', 'n/a');
$application->setCommandLoader($commandLoader);
$application->run();
