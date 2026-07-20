<?php

namespace App\Domain\Entities\Message;

final readonly class MessageInputDTO
{
    public function __construct(
        public int $chatId,
        public ?string $value,
        public ?EnumMessageButton $clickedButton,
    ) {}
}
