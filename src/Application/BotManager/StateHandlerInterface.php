<?php

namespace App\Application\BotManager;

use App\Domain\Exceptions\SentToClient\BaseSentToClientException;

interface StateHandlerInterface
{
    /**
     * @throws BaseSentToClientException
     */
    public function handle(
        int $chatId,
        ?string $text,
        ?string $payload
    ): HandlerResponseDTO;
}
