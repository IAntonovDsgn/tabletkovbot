<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumOutgoingText;
use App\Domain\Entities\Session\State\EnumState;

class StateChangeNotificationTimeSelectedHandler implements StateHandlerInterface
{
    public function handle(int $chatId, ?string $text, ?string $payload, ?string $clickedButtonTitle): StateHandlerDTO
    {
        return new StateHandlerDTO(
            EnumOutgoingText::ENTER_NOTIFICATION_TIME,
            [
                new Button(Button::MENU, EnumState::MENU)
            ]
        );
    }
}
