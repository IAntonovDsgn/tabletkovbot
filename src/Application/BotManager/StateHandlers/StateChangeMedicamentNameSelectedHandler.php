<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;

class StateChangeMedicamentNameSelectedHandler implements StateHandlerInterface
{
    public function handle(int $chatId, ?string $text, ?string $payload, ?string $clickedButtonTitle): StateHandlerDTO
    {
        return new StateHandlerDTO(
            EnumMessageText::ENTER_NEW_NAME,
            [
                new Button(Button::MENU, EnumState::MENU)
            ]
        );
    }
}
