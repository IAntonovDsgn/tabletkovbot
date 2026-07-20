<?php

namespace App\Application\BotManager;

use App\Application\BotManager\StateHandlers\ChangeNameMedicamentEnteredStateHandler;
use App\Domain\Entities\Session\State\EnumSessionState;

final readonly class FactoryStateHandler
{
    public function __construct(
        private ChangeNameMedicamentEnteredStateHandler $changeNameMedicamentEnteredStateHandler,
    ) {
    }

    public function makeByState(EnumSessionState $state): StateHandlerInterface
    {
        return match ($state) {
            EnumSessionState::CHANGE_MEDICAMENT_NAME_ENTERED => $this->changeNameMedicamentEnteredStateHandler,

        };
    }
}
