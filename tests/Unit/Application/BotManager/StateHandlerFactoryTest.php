<?php

namespace Tests\Unit\Application\BotManager;

use App\Application\BotManager\StateHandlerFactory;
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
use App\Application\BotManager\StateHandlers\StateNotificationDisabledHandler;
use App\Application\BotManager\StateHandlers\StateNotificationEnabledHandler;
use App\Application\BotManager\StateHandlers\StateNotificationsSelectedHandler;
use App\Application\BotManager\StateHandlers\StateNotifiedHandler;
use App\Application\BotManager\StateHandlers\StateSelectedMedicamentForDeleteHandler;
use App\Application\Keyboard\KeyboardFactory;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateHandlerFactoryTest extends TestCase
{
    private StateHandlerFactory $factory;
    private MockObject $medicamentRepository;
    private MockObject $intakeMarkRepository;
    private MockObject $sessionRepository;
    private MockObject $keyboardFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->intakeMarkRepository = $this->createMock(IntakeMarkRepositoryInterface::class);
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->keyboardFactory = $this->createMock(KeyboardFactory::class);

        // Instantiate all handlers with mocks
        $menuHandler = new StateMenuHandler($this->keyboardFactory);
        $changeNameMedicamentEnteredHandler = new StateChangeNameMedicamentEnteredHandler($this->medicamentRepository, $this->keyboardFactory);
        $changeMedicamentSelectedHandler = new StateChangeMedicamentSelectedHandler($this->medicamentRepository);
        $medicamentNameEnteredHandler = new StateMedicamentNameEnteredHandler($this->medicamentRepository);
        $addMedicamentSelectedHandler = new StateAddMedicamentSelectedHandler();
        $medicamentNotificationTimeEnteredHandler = new StateMedicamentNotificationTimeEnteredHandler($this->medicamentRepository, $this->keyboardFactory);
        $changeMedicamentSelectedMedicamentHandler = new StateChangeMedicamentSelectedMedicamentHandler($this->medicamentRepository);
        $changeMedicamentNameSelectedHandler = new StateChangeMedicamentNameSelectedHandler();
        $changeNotificationTimeSelectedHandler = new StateChangeNotificationTimeSelectedHandler();
        $deleteMedicamentSelectedHandler = new StateDeleteMedicamentSelectedHandler($this->medicamentRepository);
        $selectedMedicamentForDeleteHandler = new StateSelectedMedicamentForDeleteHandler();
        $deleteMedicamentConfirmedHandler = new StateDeleteMedicamentConfirmedHandler($this->medicamentRepository, $this->keyboardFactory);
        $downloadReportSelectedHandler = new StateDownloadReportSelectedHandler();
        $downloadReportStartDateEnteredHandler = new StateDownloadReportStartDateEnteredHandler($this->intakeMarkRepository, $this->keyboardFactory);
        $notificationsSelectedHandler = new StateNotificationsSelectedHandler($this->sessionRepository);
        $notificationEnabledHandler = new StateNotificationEnabledHandler($this->sessionRepository, $this->keyboardFactory);
        $notificationDisabledHandler = new StateNotificationDisabledHandler($this->sessionRepository, $this->keyboardFactory);
        $makeIntakeMarkSelectedHandler = new StateMakeIntakeMarkSelectedHandler($this->medicamentRepository);
        $intakeMarkHasMadeHandler = new StateIntakeMarkHasMadeHandler($this->medicamentRepository, $this->intakeMarkRepository, $this->keyboardFactory);
        $notifiedHandler = new StateNotifiedHandler($this->medicamentRepository, $this->intakeMarkRepository, $this->keyboardFactory);

        // Instantiate the factory with all the real handlers
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
            $this->assertInstanceOf($expectedHandlerClass, $handler, "Failed for state: {$state->value}");
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
