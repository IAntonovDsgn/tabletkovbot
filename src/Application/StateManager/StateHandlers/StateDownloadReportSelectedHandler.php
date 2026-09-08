<?php

declare(strict_types=1);

namespace App\Application\StateManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateDownloadReportSelectedHandler implements StateHandlerInterface
{
    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        return new StateHandlerResponseDTO(
            EnumMessageText::ENTER_DATE,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ]
        );
    }
}
