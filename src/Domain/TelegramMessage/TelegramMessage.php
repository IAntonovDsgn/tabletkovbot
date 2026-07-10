<?php

namespace App\Domain\TelegramMessage;

class TelegramMessage
{
    public function __construct(
        private readonly int $chatId,
        private readonly string $text,
        private ?int $id = null,
    ) {}

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
