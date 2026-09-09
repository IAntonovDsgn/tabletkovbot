<?php

namespace Tests\Unit\Application\BotManager;

use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\Factories\StateHandlerFactory;
use App\Application\StateManager\StateHandlers\StateAddMedicamentSelectedHandler;
use App\Application\StateManager\StateHandlers\StateChangeMedicamentNameSelectedHandler;
use App\Application\StateManager\StateHandlers\StateChangeMedicamentSelectedHandler;
use App\Application\StateManager\StateHandlers\StateChangeMedicamentSelectedMedicamentHandler;
use App\Application\StateManager\StateHandlers\StateChangeNameMedicamentEnteredHandler;
use App\Application\StateManager\StateHandlers\StateChangeNotificationTimeSelectedHandler;
use App\Application\StateManager\StateHandlers\StateDeleteMedicamentConfirmedHandler;
use App\Application\StateManager\StateHandlers\StateDeleteMedicamentSelectedHandler;
use App\Application\StateManager\StateHandlers\StateDownloadReportSelectedHandler;
use App\Application\StateManager\StateHandlers\StateDownloadReportStartDateEnteredHandler;
use App\Application\StateManager\StateHandlers\StateIntakeMarkHasMadeHandler;
use App\Application\StateManager\StateHandlers\StateMakeIntakeMarkSelectedHandler;
use App\Application\StateManager\StateHandlers\StateMedicamentNameEnteredHandler;
use App\Application\StateManager\StateHandlers\StateMedicamentNotificationTimeEnteredHandler;
use App\Application\StateManager\StateHandlers\StateMenuHandler;
use App\Application\StateManager\StateHandlers\StateNotificationDisabledHandler;
use App\Application\StateManager\StateHandlers\StateNotificationEnabledHandler;
use App\Application\StateManager\StateHandlers\StateNotificationsSelectedHandler;
use App\Application\StateManager\StateHandlers\StateNotifiedHandler;
use App\Application\StateManager\StateHandlers\StateSelectedMedicamentForDeleteHandler;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use PHPUnit\Framework\TestCase;

class StateHandlerFactoryTest extends TestCase
{
    private StateHandlerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $intakeMarkRepository = $this->createMock(IntakeMarkRepositoryInterface::class);
        $keyboardFactory = new KeyboardFactory();

        $menuHandler = new StateMenuHandler($keyboardFactory);
        $changeNameMedicamentEnteredHandler = new StateChangeNameMedicamentEnteredHandler(
            $medicamentRepository,
            $keyboardFactory
        );
        $changeMedicamentSelectedHandler = new StateChangeMedicamentSelectedHandler($medicamentRepository, $keyboardFactory);
        $medicamentNameEnteredHandler = new StateMedicamentNameEnteredHandler($medicamentRepository);
        $addMedicamentSelectedHandler = new StateAddMedicamentSelectedHandler();
        $medicamentNotificationTimeEnteredHandler = new StateMedicamentNotificationTimeEnteredHandler(
            $medicamentRepository,
            $keyboardFactory
        );
        $changeMedicamentSelectedMedicamentHandler = new StateChangeMedicamentSelectedMedicamentHandler(
            $medicamentRepository
        );
        $changeMedicamentNameSelectedHandler = new StateChangeMedicamentNameSelectedHandler();
        $changeNotificationTimeSelectedHandler = new StateChangeNotificationTimeSelectedHandler();
        $deleteMedicamentSelectedHandler = new StateDeleteMedicamentSelectedHandler($medicamentRepository);
        $selectedMedicamentForDeleteHandler = new StateSelectedMedicamentForDeleteHandler();
        $deleteMedicamentConfirmedHandler = new StateDeleteMedicamentConfirmedHandler(
            $medicamentRepository,
            $keyboardFactory
        );
        $downloadReportSelectedHandler = new StateDownloadReportSelectedHandler();
        $downloadReportStartDateEnteredHandler = new StateDownloadReportStartDateEnteredHandler(
            $intakeMarkRepository,
            $keyboardFactory
        );
        $notificationsSelectedHandler = new StateNotificationsSelectedHandler();
        $notificationEnabledHandler = new StateNotificationEnabledHandler($keyboardFactory);
        $notificationDisabledHandler = new StateNotificationDisabledHandler($keyboardFactory);
        $makeIntakeMarkSelectedHandler = new StateMakeIntakeMarkSelectedHandler($medicamentRepository, $keyboardFactory);
        $intakeMarkHasMadeHandler = new StateIntakeMarkHasMadeHandler(
            $medicamentRepository,
            $intakeMarkRepository,
            $keyboardFactory
        );
        $notifiedHandler = new StateNotifiedHandler($medicamentRepository, $intakeMarkRepository, $keyboardFactory);

        $this->factory = new StateHandlerFactory(
            $menuHandler,
            $changeNameMedicamentEnteredHandler,
            $changeMedicamentSelectedHandler,
            $medicamentNameEnteredHandler,
            $addMedicamentSelectedHandler,
            $medicamentNotificationTimeEnteredHandler,
            $changeMedicamentSelectedMedicamentHandler,
            $changeMedicamentNameSelectedHandler,
            $changeNotificationTimeSelectedHandler,
            $deleteMedicamentSelectedHandler,
            $selectedMedicamentForDeleteHandler,
            $deleteMedicamentConfirmedHandler,
            $downloadReportSelectedHandler,
            $downloadReportStartDateEnteredHandler,
            $notificationsSelectedHandler,
            $notificationEnabledHandler,
            $notificationDisabledHandler,
            $makeIntakeMarkSelectedHandler,
            $intakeMarkHasMadeHandler,
            $notifiedHandler
        );
    }

    public function testMakeByStateReturnsCorrectHandler(): void
    {
        foreach (self::stateProvider() as $dataSet) {
            $state = $dataSet[0];
            $expectedHandlerClass = $dataSet[1];
            $handler = $this->factory->makeByState($state);
            $this->assertInstanceOf($expectedHandlerClass, $handler, "Failed for state: $state->value");
        }
    }

    public static function stateProvider(): array
    {
        return [
            [EnumState::MENU, StateMenuHandler::class],
            [EnumState::ADD_MEDICAMENT_SELECTED, StateAddMedicamentSelectedHandler::class],
            [EnumState::MEDICAMENT_NAME_ENTERED, StateMedicamentNameEnteredHandler::class],
            [EnumState::CHANGE_MEDICAMENT_NAME_ENTERED, StateChangeNameMedicamentEnteredHandler::class],
            [EnumState::CHANGE_MEDICAMENT_SELECTED, StateChangeMedicamentSelectedHandler::class],
            [EnumState::MEDICAMENT_NOTIFICATION_TIME_ENTERED, StateMedicamentNotificationTimeEnteredHandler::class],
            [EnumState::SELECTED_MEDICAMENT_FOR_CHANGE, StateChangeMedicamentSelectedMedicamentHandler::class],
            [EnumState::CHANGE_MEDICAMENT_NAME_SELECTED, StateChangeMedicamentNameSelectedHandler::class],
            [EnumState::CHANGE_NOTIFICATION_TIME_SELECTED, StateChangeNotificationTimeSelectedHandler::class],
            [EnumState::DELETE_MEDICAMENT_SELECTED, StateDeleteMedicamentSelectedHandler::class],
            [EnumState::SELECTED_MEDICAMENT_FOR_DELETE, StateSelectedMedicamentForDeleteHandler::class],
            [EnumState::DELETE_MEDICAMENT_CONFIRMED, StateDeleteMedicamentConfirmedHandler::class],
            [EnumState::DOWNLOAD_REPORT_SELECTED, StateDownloadReportSelectedHandler::class],
            [EnumState::DOWNLOAD_REPORT_START_DATE_ENTERED, StateDownloadReportStartDateEnteredHandler::class],
            [EnumState::NOTIFICATIONS_SELECTED, StateNotificationsSelectedHandler::class],
            [EnumState::NOTIFICATION_ENABLED, StateNotificationEnabledHandler::class],
            [EnumState::NOTIFICATION_DISABLED, StateNotificationDisabledHandler::class],
            [EnumState::MAKE_INTAKE_MARK_SELECTED, StateMakeIntakeMarkSelectedHandler::class],
            [EnumState::INTAKE_MARK_HAS_MADE, StateIntakeMarkHasMadeHandler::class],
            [EnumState::NOTIFIED, StateNotifiedHandler::class],
        ];
    }
}
