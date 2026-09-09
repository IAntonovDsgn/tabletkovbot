<?php

declare(strict_types=1);

namespace App\Application\MessageService;

use App\Application\StateManager\DTOs\RequestDTO;
use App\Domain\Entities\Message\Message;

interface MessageServiceInterface
{
    public function sendMessage(Message $message): void;

    /**
     * @return RequestDTO[]
     */
    public function getUpdates(): array;
}
