<?php

namespace Presentation\console;

require __DIR__ . '/../../../bootstrap/bootstrap.php';

use App\Infrastructure\Facade\ServiceContainer\ContainerService;
use App\Presentation\console\Commands\GetTelegramUpdatesCommand;
use App\Presentation\console\Commands\SendTelegramMessageCommand;
use Symfony\Component\Console\Application;

$application = new Application('app', 'n/a');
$application->addCommand(ContainerService::get(GetTelegramUpdatesCommand::class));
$application->addCommand(ContainerService::get(SendTelegramMessageCommand::class));
$application->run();
