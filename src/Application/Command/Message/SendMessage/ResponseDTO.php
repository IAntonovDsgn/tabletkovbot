<?php

namespace App\Application\Command\Message\SendMessage;

final readonly class ResponseDTO
{
    public function __construct(
        public int $chatId,
        public string $text,
        public ?int $replyToMessageId = null,
    ) {
    }
}
