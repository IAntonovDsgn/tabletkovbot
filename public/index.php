<?php

use App\Application\BotManager\RequestDTO;
use App\Presentation\Api\Controller;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Message;

/** @var \DI\Container $container */
$container = require __DIR__ . '/../bootstrap/bootstrap.php';

$messages = Telegram::getWebhookUpdate()->getMessage();

/** @var Message $message */
$message = end($messages);

$incomingMessage = new RequestDTO(
    $message->chat->id,
);

/** @var Controller $controller */
$controller = $container->get(Controller::class);
$controller->process($incomingMessage);
