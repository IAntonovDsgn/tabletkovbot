<?php

namespace App;

require __DIR__ . '/../bootstrap/bootstrap.php';

use App\Presentation\Api\Controller;
use ServiceContainer;
use Telegram\Bot\Laravel\Facades\Telegram;

$handler = ServiceContainer::get(Controller::class);

$update = Telegram::getWebhookUpdate();

$handler->handle($update);
