<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateSelectedMedicamentForDeleteHandler implements StateHandlerInterface
{
    public function handle(
        int $chatId,
        ?string $messageText,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        return new StateHandlerResponseDTO(
            EnumMessageText::ARE_YOU_CONFIRM_DELETE_MEDICAMENT,
            [
                new MessageButton(MessageButton::CONFIRM, EnumState::DELETE_MEDICAMENT_CONFIRMED),
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ],
            $buttonPayload
        );
    }
}
