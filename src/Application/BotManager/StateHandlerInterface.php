<?php

namespace App\Application\BotManager;

use App\Domain\Exceptions\SendToClient\BaseSendToClientException;

interface StateHandlerInterface
{
    /**
     * @throws BaseSendToClientException
     */
    public function handle(
        int $chatId,
        ?string $text,
        ?string $buttonPayload,
        ?string $clickedButtonTitle
    ): StateHandlerDTO;
}
