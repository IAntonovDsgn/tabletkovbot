<?php

declare(strict_types=1);

namespace App\Application\BotManager;

final readonly class RequestDTO
{
    public function __construct(
        public int $chatId,
        public ?string $text = null,
        public ?string $payload = null,
    ) {
    }
}
