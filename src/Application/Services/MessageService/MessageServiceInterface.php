<?php

namespace App\Application\Services\MessageService;

use App\Domain\Entities\Message\EnumMessageButton;
use App\Domain\Entities\Message\MessageInputDTO;

interface MessageServiceInterface
{
    /**
     * @param EnumMessageButton[] $buttons
     */
    public function sendMessage(int $chatId, ?string $message, array $buttons): void;

    /**
     * @return MessageInputDTO[]
     */
    public function getUpdates(): array;
}
