<?php

namespace App\Domain\Entities\Message;

readonly class MessageOutputDTO
{
    /**
     * @param EnumMessageButtonType[] $buttons
     */
    public function __construct(
        public int $chatId,
        public string $message,
        public array $buttons
    ) {}
}
