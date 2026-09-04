<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;

class StateChangeMedicamentNameSelectedHandler implements StateHandlerInterface
{
    public function handle(
        int $chatId,
        ?string $messageText,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        return new StateHandlerResponseDTO(
            EnumMessageText::ENTER_NEW_NAME,
            []
        );
    }
}
