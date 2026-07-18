<?php

namespace App\Application\BotManager;

use App\Application\BotManager\StateHandlers\ChangeNameMedicamentEnteredStateHandler;
use App\Domain\Entities\Session\State\EnumSessionState;
use App\Domain\Entities\Session\State\StateHandlerInterface;

final readonly class FactoryStateHandler
{
    public function __construct(
        private ChangeNameMedicamentEnteredStateHandler $changeNameMedicamentEnteredStateHandler,
    ) {
    }

    public function make(EnumSessionState $state): StateHandlerInterface
    {
        return match ($state) {
            EnumSessionState::CHANGE_MEDICAMENT_NAME_ENTERED => $this->changeNameMedicamentEnteredStateHandler,

        };
    }
}
