<?php

namespace App\Application\BotManager;

use App\Application\BotManager\StateHandlers\StateAddMedicamentSelectedHandler;
use App\Application\BotManager\StateHandlers\StateChangeMedicamentNameSelectedHandler;
use App\Application\BotManager\StateHandlers\StateChangeMedicamentSelectedHandler;
use App\Application\BotManager\StateHandlers\StateChangeMedicamentSelectedMedicamentHandler;
use App\Application\BotManager\StateHandlers\StateChangeNameMedicamentEnteredHandler;
use App\Application\BotManager\StateHandlers\StateMedicamentNameEnteredHandler;
use App\Application\BotManager\StateHandlers\StateMedicamentNotificationTimeEnteredHandler;
use App\Application\BotManager\StateHandlers\StateMenuHandler;
use App\Domain\Entities\Session\State\EnumState;

final readonly class FactoryStateHandler
{
    public function __construct(
        private StateMenuHandler $menuHandler,
        private StateChangeNameMedicamentEnteredHandler $changeNameMedicamentEnteredHandler,
        private StateChangeMedicamentSelectedHandler $changeMedicamentSelectedHandler,
        private StateMedicamentNameEnteredHandler $medicamentNameEnteredHandler,
        private StateAddMedicamentSelectedHandler $addMedicamentSelectedHandler,
        private StateMedicamentNotificationTimeEnteredHandler $medicamentNotificationTimeEnteredHandler,
        private StateChangeMedicamentSelectedMedicamentHandler $changeMedicamentSelectedMedicamentHandler,
        private StateChangeMedicamentNameSelectedHandler $changeMedicamentNameSelectedHandler,
    ) {
    }

    public function makeByState(EnumState $state): StateHandlerInterface
    {
        return match ($state) {
            EnumState::MENU => $this->menuHandler,
            EnumState::ADD_MEDICAMENT_SELECTED => $this->addMedicamentSelectedHandler,
            EnumState::MEDICAMENT_NAME_ENTERED => $this->medicamentNameEnteredHandler,
            EnumState::CHANGE_MEDICAMENT_NAME_ENTERED => $this->changeNameMedicamentEnteredHandler,
            EnumState::CHANGE_MEDICAMENT_SELECTED => $this->changeMedicamentSelectedHandler,
            EnumState::MEDICAMENT_NOTIFICATION_TIME_ENTERED => $this->medicamentNotificationTimeEnteredHandler,
            EnumState::CHANGE_MEDICAMENT_SELECTED_MEDICAMENT => $this->changeMedicamentSelectedMedicamentHandler,
            EnumState::CHANGE_MEDICAMENT_NAME_SELECTED => $this->changeMedicamentNameSelectedHandler,
        };
    }
}
