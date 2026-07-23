<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumOutgoingText;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateSelectedMedicamentForDeleteHandler implements StateHandlerInterface
{
    public function handle(int $chatId, ?string $text, ?string $payload, ?string $clickedButtonTitle): StateHandlerDTO
    {
        return new StateHandlerDTO(
            EnumOutgoingText::ARE_YOU_CONFIRM_DELETE_MEDICAMENT,
            [
                new Button(Button::CONFIRM, EnumState::DELETE_MEDICAMENT_CONFIRMED),
                new Button(Button::MENU, EnumState::MENU)
            ]
        );
    }
}
