<?php

namespace App\Application\Services\MessageService;

use App\Application\BotManager\MessageInputDTO;
use App\Domain\Entities\Message\EnumMessageButton;

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
