<?php

namespace App;

require __DIR__ . '/../bootstrap/bootstrap.php';

use App\Infrastructure\ServiceContainer\ServiceContainer;
use App\Presentation\Api\Controller;
use Telegram\Bot\Laravel\Facades\Telegram;

$handler = ServiceContainer::get(Controller::class);

$update = Telegram::getWebhookUpdate();

$handler->handle($update);
