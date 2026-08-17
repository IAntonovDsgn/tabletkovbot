<?php

declare(strict_types=1);

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
