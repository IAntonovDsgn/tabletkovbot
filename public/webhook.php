<?php

require __DIR__ . '/../bootstrap/bootstrap.php';

use App\Infrastructure\Facade\ServiceContainer\ServiceContainer;
use App\Presentation\Api\Controller;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

/** @var Controller $controller */
$controller = ServiceContainer::get(Controller::class);
$update = Telegram::getWebhookUpdate();

try {
    $controller->handleUpdatesAction($update);
} catch (App\Presentation\Api\ControllerException $e) {
    Log::error($e);
}
