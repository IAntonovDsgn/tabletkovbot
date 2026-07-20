<?php

namespace App\Application\BotManager;

use App\Application\BotManager\StateHandlers\AddMedicamentSelectedHandler;
use App\Application\BotManager\StateHandlers\ChangeNameMedicamentEnteredHandler;
use App\Domain\Entities\Session\State\EnumState;

final readonly class FactoryStateHandler
{
    public function __construct(
        private ChangeNameMedicamentEnteredHandler $changeNameMedicamentEnteredStateHandler,
        private AddMedicamentSelectedHandler $addMedicamentSelectedStateHandler,
    ) {
    }

    public function makeByState(EnumState $state): StateHandlerInterface
    {
        return match ($state) {
            EnumState::CHANGE_MEDICAMENT_NAME_ENTERED => $this->changeNameMedicamentEnteredStateHandler,
            EnumState::ADD_MEDICAMENT_SELECTED => $this->addMedicamentSelectedStateHandler,
        };
    }
}
