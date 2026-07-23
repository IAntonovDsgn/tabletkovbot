<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateDownloadReportSelectedHandler implements StateHandlerInterface
{
    public function handle(int $chatId, ?string $text, ?string $payload, ?string $clickedButtonTitle): StateHandlerDTO
    {
        return new StateHandlerDTO(
            EnumMessageText::ENTER_DATE,
            [
                new Button(Button::MENU, EnumState::MENU)
            ]
        );
    }
}
