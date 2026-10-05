<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager;

use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\Factories\StateHandlerFactory;
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
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class StateHandlerFactoryTest extends TestCase
{
    private StateHandlerFactory $factory;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = $this->buildFactory();
    }

    /**
     * @throws ReflectionException
     */
    private function buildFactory(): StateHandlerFactory
    {
        return new StateHandlerFactory(...$this->argumentsFor(StateHandlerFactory::class));
    }

    /**
     * @return object[]
     * @throws ReflectionException
     */
    private function argumentsFor(string $class): array
    {
        $arguments = [];

        foreach (new ReflectionClass($class)->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType) {
                self::fail("$class must only take typed constructor dependencies");
            }

            $arguments[] = $this->dependency($type->getName());
        }

        return $arguments;
    }

    /**
     * @throws ReflectionException
     */
    private function dependency(string $class): object
    {
        // Stateless collaborator: a real instance is cheap and needs no wiring.
        if ($class === KeyboardFactory::class) {
            return new KeyboardFactory();
        }

        if (interface_exists($class)) {
            return $this->createMock($class);
        }

        /** @var object $instance */
        $instance = new ReflectionClass($class)->newInstanceArgs($this->argumentsFor($class));

        return $instance;
    }

    /**
     * @return array<string, array{0: EnumState, 1: class-string<StateHandlerInterface>}>
     */
    private static function stateProvider(): array
    {
        return [
            'MENU' => [EnumState::MENU, StateMenuHandler::class],
            'ADD_MEDICAMENT_SELECTED' => [EnumState::ADD_MEDICAMENT_SELECTED, StateAddMedicamentSelectedHandler::class],
            'MEDICAMENT_NAME_ENTERED' => [EnumState::MEDICAMENT_NAME_ENTERED, StateMedicamentNameEnteredHandler::class],
            'CHANGE_MEDICAMENT_NAME_ENTERED' => [
                EnumState::CHANGE_MEDICAMENT_NAME_ENTERED,
                StateChangeNameMedicamentEnteredHandler::class,
            ],
            'CHANGE_MEDICAMENT_SELECTED' => [
                EnumState::CHANGE_MEDICAMENT_SELECTED,
                StateChangeMedicamentSelectedHandler::class,
            ],
            'MEDICAMENT_NOTIFICATION_TIME_ENTERED' => [
                EnumState::MEDICAMENT_NOTIFICATION_TIME_ENTERED,
                StateMedicamentNotificationTimeEnteredHandler::class,
            ],
            'SELECTED_MEDICAMENT_FOR_CHANGE' => [
                EnumState::SELECTED_MEDICAMENT_FOR_CHANGE,
                StateChangeMedicamentSelectedMedicamentHandler::class,
            ],
            'CHANGE_MEDICAMENT_NAME_SELECTED' => [
                EnumState::CHANGE_MEDICAMENT_NAME_SELECTED,
                StateChangeMedicamentNameSelectedHandler::class,
            ],
            'CHANGE_NOTIFICATION_TIME_SELECTED' => [
                EnumState::CHANGE_NOTIFICATION_TIME_SELECTED,
                StateChangeNotificationTimeSelectedHandler::class,
            ],
            'DELETE_MEDICAMENT_SELECTED' => [
                EnumState::DELETE_MEDICAMENT_SELECTED,
                StateDeleteMedicamentSelectedHandler::class,
            ],
            'SELECTED_MEDICAMENT_FOR_DELETE' => [
                EnumState::SELECTED_MEDICAMENT_FOR_DELETE,
                StateSelectedMedicamentForDeleteHandler::class,
            ],
            'DELETE_MEDICAMENT_CONFIRMED' => [
                EnumState::DELETE_MEDICAMENT_CONFIRMED,
                StateDeleteMedicamentConfirmedHandler::class,
            ],
            'DOWNLOAD_REPORT_SELECTED' => [
                EnumState::DOWNLOAD_REPORT_SELECTED,
                StateDownloadReportSelectedHandler::class,
            ],
            'DOWNLOAD_REPORT_START_DATE_ENTERED' => [
                EnumState::DOWNLOAD_REPORT_START_DATE_ENTERED,
                StateDownloadReportStartDateEnteredHandler::class,
            ],
            'NOTIFICATIONS_SELECTED' => [EnumState::NOTIFICATIONS_SELECTED, StateNotificationsSelectedHandler::class],
            'NOTIFICATION_ENABLED' => [EnumState::NOTIFICATION_ENABLED, StateNotificationEnabledHandler::class],
            'NOTIFICATION_DISABLED' => [EnumState::NOTIFICATION_DISABLED, StateNotificationDisabledHandler::class],
            'MAKE_INTAKE_MARK_SELECTED' => [
                EnumState::MAKE_INTAKE_MARK_SELECTED,
                StateMakeIntakeMarkSelectedHandler::class,
            ],
            'INTAKE_MARK_HAS_MADE' => [EnumState::INTAKE_MARK_HAS_MADE, StateIntakeMarkHasMadeHandler::class],
            'NOTIFIED' => [EnumState::NOTIFIED, StateNotifiedHandler::class],
        ];
    }

    public function testMakeByStateReturnsCorrectHandler(): void
    {
        foreach (self::stateProvider() as $dataSet) {
            [$state, $expectedHandlerClass] = $dataSet;

            $this->assertInstanceOf(
                $expectedHandlerClass,
                $this->factory->makeByState($state),
                "Failed for state: $state->value",
            );
        }
    }

    public function testEveryStateIsMappedToADistinctHandler(): void
    {
        $mapped = [];

        foreach (EnumState::cases() as $state) {
            $handlerClass = $this->factory->makeByState($state)::class;
            $mapped[$state->value] ??= $handlerClass;

            $this->assertSame($mapped[$state->value], $handlerClass, "State $state->value maps to a duplicate handler");
        }

        self::assertSame(
            array_map(static fn(EnumState $state): string => $state->value, EnumState::cases()),
            array_keys($mapped),
            'makeByState() must cover every EnumState case',
        );
    }

    public function testConstructorTakesOneHandlerPerState(): void
    {
        $constructorCount = count(new ReflectionClass(StateHandlerFactory::class)->getConstructor()?->getParameters() ?? []);
        $stateCount = count(EnumState::cases());

        $this->assertSame(
            $stateCount,
            $constructorCount,
            'StateHandlerFactory needs exactly one handler dependency per conversation state',
        );
    }
}
