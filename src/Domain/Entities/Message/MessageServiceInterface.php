<?php

namespace App\Domain\Entities\Message;

use App\Presentation\Api\Request;

interface MessageServiceInterface
{
    /**
     * @param EnumMessageButtonType[] $buttons
     */
    public function sendMessage(int $chatId, ?string $message, array $buttons): void;

    /**
     * @return Request[]
     */
    public function getUpdates(): array;
}
