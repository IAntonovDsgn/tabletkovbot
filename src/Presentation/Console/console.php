<?php

namespace Presentation\console;

require __DIR__ . '/../../../bootstrap/bootstrap.php';

use App\Infrastructure\Facade\ServiceContainer\ServiceContainer;
use App\Presentation\console\Command\GetTelegramUpdatesCommand;
use App\Presentation\console\Command\SendTelegramMessageCommand;
use Symfony\Component\Console\Application;

$application = new Application('app', 'n/a');
$application->addCommand(ServiceContainer::get(GetTelegramUpdatesCommand::class));
$application->addCommand(ServiceContainer::get(SendTelegramMessageCommand::class));
$application->run();
