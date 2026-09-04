<?php

declare(strict_types=1);

namespace App\Application\BotManager;

interface StateHandlerInterface
{
    public function handle(
        int $chatId,
        ?string $messageText,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO;
}
