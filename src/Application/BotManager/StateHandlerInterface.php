<?php

namespace App\Application\BotManager;

use App\Domain\Exceptions\External\InvalidValueException;

interface StateHandlerInterface
{
    /**
     * @throws InvalidValueException
     */
    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO;
}
