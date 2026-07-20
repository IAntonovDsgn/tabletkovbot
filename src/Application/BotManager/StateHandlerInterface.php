<?php

namespace App\Application\BotManager;

use App\Domain\Exceptions\BaseDomainException;

interface StateHandlerInterface
{
    /**
     * @throws BaseDomainException
     */
    public function handle(
        int $chatId,
        ?string $text,
        ?string $payload
    ): HandlerResponseDTO;
}
