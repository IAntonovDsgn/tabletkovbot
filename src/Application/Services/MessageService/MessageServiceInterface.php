<?php

namespace App\Application\Services\MessageService;

use App\Application\BotManager\RequestDTO;
use App\Domain\Entities\Message\Message;

interface MessageServiceInterface
{
    public function sendMessage(Message $message): void;

    /**
     * @return RequestDTO[]
     */
    public function getUpdates(): array;
}
