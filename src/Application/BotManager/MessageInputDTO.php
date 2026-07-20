<?php

namespace App\Application\BotManager;

use App\Domain\Entities\Message\EnumMessageButton;

final readonly class MessageInputDTO
{
    public function __construct(
        public int $chatId,
        public ?string $value,
        public ?EnumMessageButton $clickedButton,
    ) {}
}
