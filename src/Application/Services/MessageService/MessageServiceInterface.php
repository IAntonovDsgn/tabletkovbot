<?php

namespace App\Application\Services\MessageService;

use App\Application\BotManager\MessageInputDTO;
use App\Domain\Entities\Message\Message;

interface MessageServiceInterface
{
    public function sendMessage(Message $message): void;

    /**
     * @return MessageInputDTO[]
     */
    public function getUpdates(): array;
}
