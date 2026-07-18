<?php

require __DIR__ . '/../bootstrap/bootstrap.php';

use App\Domain\Entities\Message\MessageInputDTO;
use App\Infrastructure\Facade\ServiceContainer\ContainerService;
use App\Presentation\Api\Controller;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Message;

$messages = Telegram::getWebhookUpdate()->getMessage();

/** @var Message $message */
$message = end($messages);

$incomingMessage = new MessageInputDTO(
    $message->chat->id,
    $message->sender_tag,
    $message->text,
);

/** @var Controller $controller */
$controller = ContainerService::get(Controller::class);
$controller->process($incomingMessage);
