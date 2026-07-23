<?php

namespace App\Application\BotManager;

use App\Application\BotManager\StateHandlers\StateAddMedicamentSelectedHandler;
use App\Application\BotManager\StateHandlers\StateChangeMedicamentNameSelectedHandler;
use App\Application\BotManager\StateHandlers\StateChangeMedicamentSelectedHandler;
use App\Application\BotManager\StateHandlers\StateChangeMedicamentSelectedMedicamentHandler;
use App\Application\BotManager\StateHandlers\StateChangeNameMedicamentEnteredHandler;
use App\Application\BotManager\StateHandlers\StateChangeNotificationTimeSelectedHandler;
use App\Application\BotManager\StateHandlers\StateDeleteMedicamentConfirmedHandler;
use App\Application\BotManager\StateHandlers\StateDeleteMedicamentSelectedHandler;
use App\Application\BotManager\StateHandlers\StateDownloadReportSelectedHandler;
use App\Application\BotManager\StateHandlers\StateDownloadReportStartDateEnteredHandler;
use App\Application\BotManager\StateHandlers\StateIntakeMarkHasMadeHandler;
use App\Application\BotManager\StateHandlers\StateMakeIntakeMarkSelectedHandler;
use App\Application\BotManager\StateHandlers\StateMedicamentNameEnteredHandler;
use App\Application\BotManager\StateHandlers\StateMedicamentNotificationTimeEnteredHandler;
use App\Application\BotManager\StateHandlers\StateMenuHandler;
use App\Application\BotManager\StateHandlers\StateNotificationModeSelectedHandler;
use App\Application\BotManager\StateHandlers\StateNotificationsSelectedHandler;
use App\Application\BotManager\StateHandlers\StateNotifiedHandler;
use App\Application\BotManager\StateHandlers\StateSelectedMedicamentForDeleteHandler;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateHandlerFactory
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
        private StateChangeNotificationTimeSelectedHandler $changeNotificationTimeSelectedHandler,
        private StateDeleteMedicamentSelectedHandler $deleteMedicamentSelectedHandler,
        private StateSelectedMedicamentForDeleteHandler $selectedMedicamentForDeleteHandler,
        private StateDeleteMedicamentConfirmedHandler $deleteMedicamentConfirmedHandler,
        private StateDownloadReportSelectedHandler $downloadReportSelectedHandler,
        private StateDownloadReportStartDateEnteredHandler $downloadReportStartDateEnteredHandler,
        private StateNotificationsSelectedHandler $notificationsSelectedHandler,
        private StateNotificationModeSelectedHandler $notificationModeSelectedHandler,
        private StateMakeIntakeMarkSelectedHandler $makeIntakeMarkSelectedHandler,
        private StateIntakeMarkHasMadeHandler $intakeMarkHasMadeHandler,
        private StateNotifiedHandler $notifiedHandler,
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
            EnumState::SELECTED_MEDICAMENT_FOR_CHANGE => $this->changeMedicamentSelectedMedicamentHandler,
            EnumState::CHANGE_MEDICAMENT_NAME_SELECTED => $this->changeMedicamentNameSelectedHandler,
            EnumState::CHANGE_NOTIFICATION_TIME_SELECTED => $this->changeNotificationTimeSelectedHandler,
            EnumState::DELETE_MEDICAMENT_SELECTED => $this->deleteMedicamentSelectedHandler,
            EnumState::SELECTED_MEDICAMENT_FOR_DELETE => $this->selectedMedicamentForDeleteHandler,
            EnumState::DELETE_MEDICAMENT_CONFIRMED => $this->deleteMedicamentConfirmedHandler,
            EnumState::DOWNLOAD_REPORT_SELECTED => $this->downloadReportSelectedHandler,
            EnumState::DOWNLOAD_REPORT_START_DATE_ENTERED => $this->downloadReportStartDateEnteredHandler,
            EnumState::NOTIFICATIONS_SELECTED => $this->notificationsSelectedHandler,
            EnumState::NOTIFICATION_MODE_SELECTED => $this->notificationModeSelectedHandler,
            EnumState::MAKE_INTAKE_MARK_SELECTED => $this->makeIntakeMarkSelectedHandler,
            EnumState::INTAKE_MARK_HAS_MADE => $this->intakeMarkHasMadeHandler,
            EnumState::NOTIFIED => $this->notifiedHandler,
        };
    }
}
