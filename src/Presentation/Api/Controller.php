<?php

namespace App\Presentation\Api;

use App\Application\Command\Message\SendMessage\Handler as SendMessageHandler;
use App\Application\Command\Session\TransitToState\Handler as TransitToStateHandler;
use Telegram\Bot\Objects\Update;
use Throwable;

final readonly class Controller
{
    public function __construct(
        private TransitToStateHandler $transitionToStateHandler,
        private SendMessageHandler $sendMessageHandler,
    )
    {}

    /**
     * @throws ControllerException
     */
    public function handleUpdatesAction(Update $requestData): void
    {
        try {
            $messages = $requestData->getMessage();
            $message = end($messages);
            $this->transitionToStateHandler->handle($message['chatId'], $message['considerState']);
            $this->sendMessageHandler->handle();
        } catch (Throwable $e) {
            throw new ControllerException($e->getMessage());
        }
    }
}
