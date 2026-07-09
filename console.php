<?php

namespace Presentation\console;

require __DIR__ . '/bootstrap/bootstrap.php';

use App\Presentation\console\Commands\GetTelegramUpdatesCommand;
use App\Presentation\console\Commands\SendTelegramMessageCommand;
use Symfony\Component\Console\Application;

$application = new Application('app', 'n/a');
$application->addCommand(new GetTelegramUpdatesCommand());
$application->addCommand(new SendTelegramMessageCommand());
$application->run();
