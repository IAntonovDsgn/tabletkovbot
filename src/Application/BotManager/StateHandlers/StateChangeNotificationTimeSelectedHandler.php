<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;

class StateChangeNotificationTimeSelectedHandler implements StateHandlerInterface
{
    public function handle(int $chatId, ?string $text, ?string $sessionPayload, ?string $buttonPayload): StateHandlerResponseDTO
    {
        return new StateHandlerResponseDTO(
            EnumMessageText::ENTER_TIME,
            [
                new Button(Button::MENU, EnumState::MENU)
            ]
        );
    }
}
