<?php

namespace App\Application\BotManager;

interface StateHandlerInterface
{
    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO;
}
