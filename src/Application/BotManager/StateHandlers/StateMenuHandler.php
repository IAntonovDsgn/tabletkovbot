<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateMenuHandler implements StateHandlerInterface
{
    public function handle(int $chatId, ?string $text, ?string $sessionPayload, ?string $buttonPayload): StateHandlerResponseDTO
    {
        return new StateHandlerResponseDTO(
            EnumMessageText::MENU,
            [
                new Button(Button::MAKE_INTAKE_MARK_BUTTON_TITLE, EnumState::MAKE_INTAKE_MARK_SELECTED),
                new Button(Button::ADD_MEDICAMENT_BUTTON_TITLE, EnumState::ADD_MEDICAMENT_SELECTED),
                new Button(Button::CHANGE_MEDICAMENT_BUTTON_TITLE, EnumState::CHANGE_MEDICAMENT_SELECTED),
                new Button(Button::DELETE_MEDICAMENT_BUTTON_TITLE, EnumState::DELETE_MEDICAMENT_SELECTED),
                new Button(Button::DOWNLOAD_REPORT_BUTTON_TITLE, EnumState::DOWNLOAD_REPORT_SELECTED),
                new Button(Button::NOTIFICATIONS_BUTTON_TITLE, EnumState::NOTIFICATIONS_SELECTED),
            ],
        );
    }
}
