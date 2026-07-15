<?php

namespace App\Presentation\Api;

use App\Application\Command\Message\SendMessage\Handler as SendMessageHandler;
use App\Application\Command\Message\SendMessage\ResponseDTO;
use App\Application\Command\Session\TransitToState\Handler as TransitToStateHandler;
use App\Application\Command\Session\TransitToState\RequestDTO;
use Telegram\Bot\Objects\Message;
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
    public function handleTelegramWebhookAction(Update $update): void
    {
        try {
            $message = $this->getMessageFromTelegramUpdate($update);
            $requestDTO = $this->makeRequestDTO($message);
            $this->transitionToStateHandler->handle($requestDTO);

            $responseDTO = $this->makeResponseDTO();
            $this->sendMessageHandler->handle($responseDTO);

        } catch (Throwable $e) {
            throw new ControllerException($e->getMessage());
        }
    }

    /**
     * @throws ControllerException
     */
    private function makeRequestDTO(Message $message): RequestDTO
    {
        try {
            return new RequestDTO($message['chatId'], $message['newState']);
        } catch (Throwable $e) {
            throw new ControllerException($e->getMessage());
        }
    }

    private function getMessageFromTelegramUpdate(Update $update): Message
    {
        $messages = $update->getMessage();
        return end($messages);
    }

    private function makeResponseDTO(): ResponseDTO
    {

    }
}
