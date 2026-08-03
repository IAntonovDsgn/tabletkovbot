<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateSelectedMedicamentForDeleteHandler implements StateHandlerInterface
{
    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        return new StateHandlerResponseDTO(
            EnumMessageText::ARE_YOU_CONFIRM_DELETE_MEDICAMENT,
            [
                new Button(Button::CONFIRM, EnumState::DELETE_MEDICAMENT_CONFIRMED),
                new Button(Button::MENU, EnumState::MENU)
            ]
        );
    }
}
