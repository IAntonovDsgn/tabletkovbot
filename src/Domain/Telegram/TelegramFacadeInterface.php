<?php

namespace App\Domain\Telegram;

interface TelegramFacadeInterface
{
    public function sendMessage(
        string $chatId,
        string $message,
        ?int $replyToMessageId = null,
        ?string $parseMode = 'html'
    ): void;

    /**
     * @return array<string,mixed>
     */
    public function getUpdates(): array;
}
