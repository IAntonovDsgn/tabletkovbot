<?php

declare(strict_types=1);

namespace App\Application\StateManager;

final readonly class RequestDTO
{
    public function __construct(
        public int $chatId,
        public ?string $messageText = null,
        public ?string $payload = null,
    ) {}
}
