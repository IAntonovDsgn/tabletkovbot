<?php

namespace App\Domain\Message;

class Message
{
    public function __construct(
        private readonly int $chatId,
        private readonly string $text,
        private readonly ?int $replyToMessageId = null,
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

    public function getReplyToMessageId(): ?int
    {
        return $this->replyToMessageId;
    }
}
