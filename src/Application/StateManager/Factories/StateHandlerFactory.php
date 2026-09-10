<?php

declare(strict_types=1);

namespace App\Application\StateManager\Factories;

use App\Application\StateManager\UseCases\StateAddMedicamentSelectedHandler;
use App\Application\StateManager\UseCases\StateChangeMedicamentNameSelectedHandler;
use App\Application\StateManager\UseCases\StateChangeMedicamentSelectedHandler;
use App\Application\StateManager\UseCases\StateChangeMedicamentSelectedMedicamentHandler;
use App\Application\StateManager\UseCases\StateChangeNameMedicamentEnteredHandler;
use App\Application\StateManager\UseCases\StateChangeNotificationTimeSelectedHandler;
use App\Application\StateManager\UseCases\StateDeleteMedicamentConfirmedHandler;
use App\Application\StateManager\UseCases\StateDeleteMedicamentSelectedHandler;
use App\Application\StateManager\UseCases\StateDownloadReportSelectedHandler;
use App\Application\StateManager\UseCases\StateDownloadReportStartDateEnteredHandler;
use App\Application\StateManager\UseCases\StateHandlerInterface;
use App\Application\StateManager\UseCases\StateIntakeMarkHasMadeHandler;
use App\Application\StateManager\UseCases\StateMakeIntakeMarkSelectedHandler;
use App\Application\StateManager\UseCases\StateMedicamentNameEnteredHandler;
use App\Application\StateManager\UseCases\StateMedicamentNotificationTimeEnteredHandler;
use App\Application\StateManager\UseCases\StateMenuHandler;
use App\Application\StateManager\UseCases\StateNotificationDisabledHandler;
use App\Application\StateManager\UseCases\StateNotificationEnabledHandler;
use App\Application\StateManager\UseCases\StateNotificationsSelectedHandler;
use App\Application\StateManager\UseCases\StateNotifiedHandler;
use App\Application\StateManager\UseCases\StateSelectedMedicamentForDeleteHandler;
use App\Domain\Entities\Session\States\EnumState;

readonly class StateHandlerFactory
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
        private StateNotificationEnabledHandler $notificationEnabledHandler,
        private StateNotificationDisabledHandler $notificationDisabledHandler,
        private StateMakeIntakeMarkSelectedHandler $makeIntakeMarkSelectedHandler,
        private StateIntakeMarkHasMadeHandler $intakeMarkHasMadeHandler,
        private StateNotifiedHandler $notifiedHandler,
    ) {}

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
            EnumState::NOTIFICATION_ENABLED => $this->notificationEnabledHandler,
            EnumState::NOTIFICATION_DISABLED => $this->notificationDisabledHandler,
            EnumState::MAKE_INTAKE_MARK_SELECTED => $this->makeIntakeMarkSelectedHandler,
            EnumState::INTAKE_MARK_HAS_MADE => $this->intakeMarkHasMadeHandler,
            EnumState::NOTIFIED => $this->notifiedHandler,
        };
    }
}
