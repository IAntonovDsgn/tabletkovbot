<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\HandlerOutputDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Message\EnumOutgoingText;

final class StateAddMedicamentSelectedHandler implements StateHandlerInterface
{
    public function handle(int $chatId, ?string $text, ?string $payload, ?string $clickedButtonTitle): HandlerOutputDTO
    {
        return new HandlerOutputDTO(EnumOutgoingText::ENTER_NEW_NAME, []);
    }

}
