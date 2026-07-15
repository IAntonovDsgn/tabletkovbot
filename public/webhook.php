<?php

require __DIR__ . '/../bootstrap/bootstrap.php';

use App\Infrastructure\Facade\Log\Log;
use App\Infrastructure\Facade\ServiceContainer\ServiceContainer;
use App\Presentation\Api\Controller;
use Telegram\Bot\Laravel\Facades\Telegram;

/** @var Controller $controller */
$controller = ServiceContainer::get(Controller::class);
$update = Telegram::getWebhookUpdate();

try {
    $controller->handleTelegramWebhookAction($update);
} catch (App\Presentation\Api\ControllerException $e) {
    Log::error($e);
}
