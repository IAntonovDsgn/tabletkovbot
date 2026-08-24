<?php

namespace Tests\Unit\Application\BotManager;

use App\Application\BotManager\Manager;
use App\Application\BotManager\RequestDTO;
use App\Application\BotManager\StateHandlerFactory;
use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Persistence\OutboxRepositoryInterface;
use App\Application\Persistence\UnitOfWorkInterface;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\External\InvalidValueException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function DI\create;

class ManagerTest extends TestCase
{
    private MockObject $factoryStateHandler;
    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private MockObject $keyboardFactory;
    private MockObject $unitOfWork;
    private Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factoryStateHandler = $this->createMock(StateHandlerFactory::class);
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(OutboxRepositoryInterface::class);
        $this->keyboardFactory = $this->createMock(KeyboardFactory::class);
        $this->unitOfWork = $this->createMock(UnitOfWorkInterface::class);

        $this->manager = new Manager(
            $this->factoryStateHandler,
            $this->sessionRepository,
            $this->outboxRepository,
            $this->keyboardFactory,
            $this->unitOfWork
        );
    }

    public function testProcessNewUserMenuRequest(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'start', null);
        $initialSession = Session::create($chatId); // What a new session looks like

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->once())->method('commit');
        $this->unitOfWork->expects($this->never())->method('rollback');

        // Session handling for a new user
        $this->sessionRepository->expects($this->exactly(2)) // Find (null), Save (new session), Find (existing), Save (updated session)
            ->method('findByChatId')
            ->with($chatId)
            ->willReturnOnConsecutiveCalls(null, $initialSession); // First call returns null, second call returns the newly created session

        // Mock the StateHandler
        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->expects($this->once())
            ->method('makeByState')
            ->with(EnumState::MENU)
            ->willReturn($mockStateHandler);

        $handlerResponseDTO = new StateHandlerResponseDTO(EnumMessageText::MENU, []);
        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->with($chatId, 'start', null, null)
            ->willReturn($handlerResponseDTO);

        // Session save calls (for state transition and for payload update)
        $this->sessionRepository->expects($this->exactly(2))
            ->method('save')
            ->with($this->callback(function (Session $session) use ($chatId) {
                // Ensure the session is for the correct chat ID and has transitioned to MENU
                return $session->getChatId() === $chatId && $session->getState() === EnumState::MENU;
            }));

        // Outbox save
        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId && $message->getText() === EnumMessageText::MENU->value;
            }));

        $this->manager->process($requestDTO);
    }

    public function testProcessExistingUserWithPayloadTransition(): void
    {
        $chatId = 123;
        $payloadState = EnumState::ADD_MEDICAMENT_SELECTED;
        $requestDTO = new RequestDTO($chatId, 'some_text', $payloadState->value);

        $existingSession = Session::create($chatId); // Defaults to MENU
        $existingSession->transitionToState(EnumState::MENU); // Ensure initial state is MENU

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->once())->method('commit');
        $this->unitOfWork->expects($this->never())->method('rollback');

        // Session handling
        $this->sessionRepository->expects($this->exactly(2)) // First find, second find after handler
            ->method('findByChatId')
            ->with($chatId)
            ->willReturnOnConsecutiveCalls($existingSession, $existingSession);

        // Mock the StateHandler
        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->expects($this->once())
            ->method('makeByState')
            ->with($payloadState)
            ->willReturn($mockStateHandler);

        $handlerResponseDTO = new StateHandlerResponseDTO(EnumMessageText::ENTER_NEW_NAME, [], 'new_payload_data');
        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->with($chatId, 'some_text', null, null) // No session payload initially, button payload is null
            ->willReturn($handlerResponseDTO);

        // Session save calls (for state transition and for payload update)
        $this->sessionRepository->expects($this->exactly(2))
            ->method('save')
            ->with($this->callback(function (Session $session) use ($chatId, $payloadState, $handlerResponseDTO) {
                // First save: check state transition
                // Second save: check new payload
                return $session->getChatId() === $chatId &&
                       ($session->getState() === $payloadState || $session->getPayload() === $handlerResponseDTO->newSessionPayload);
            }));

        // Outbox save
        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId, $handlerResponseDTO) {
                $messageText = $handlerResponseDTO->messageText;
                $messageTextValue = $messageText?->value;
                return $message->getChatId() === $chatId &&
                       $message->getText() === $messageTextValue;
            }));

        $this->manager->process($requestDTO);
    }

    public function testProcessHandlesInvalidValueException(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'invalid_input', null);
        $existingSession = Session::create($chatId);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->never())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback');

        $this->sessionRepository->expects($this->exactly(2)) // Initial find, then find in errorHandler
             ->method('findByChatId')
             ->with($chatId)
             ->willReturnOnConsecutiveCalls($existingSession, $existingSession);

        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->method('makeByState')->willReturn($mockStateHandler);

        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->willThrowException(new InvalidValueException('Error message'));

        $this->sessionRepository->expects($this->exactly(2)) // Initial save (before handler throws), and save in errorHandler
             ->method('save')
             ->with($this->callback(function (Session $session) {
                 return $session->getState() === EnumState::MENU && $session->getPayload() === null;
             }));

        $this->keyboardFactory->expects($this->once())->method('makeMenuKeyboard')->willReturn([]);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId && $message->getText() === 'Error message';
            }));

        $this->manager->process($requestDTO);
    }

    public function testProcessHandlesGeneralThrowable(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'error', null);
        $existingSession = Session::create($chatId);
        $expectedException = new class extends \Exception {};

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->never())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback');

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($existingSession);

        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->method('makeByState')->willReturn($mockStateHandler);

        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->willThrowException($expectedException);

        // Ensure errorHandler is NOT called for general Throwables
        $this->sessionRepository->expects($this->once())->method('findByChatId'); // Initial find, not second find from errorHandler
        $this->sessionRepository->expects($this->once())->method('save'); // This will be called before the exception from handler
        $this->outboxRepository->expects($this->never())->method('insert');
        $this->keyboardFactory->expects($this->never())->method('makeMenuKeyboard');


        $this->expectException(get_class($expectedException));
        $this->manager->process($requestDTO);
    }

    // Add more tests for getNextState logic, specifically edge cases and other paths
    public function testGetNextStateThrowsInvalidValueExceptionForInvalidPayload(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'text', 'INVALID_STATE'); // This payload will trigger InvalidValueException
        $existingSession = Session::create($chatId);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->never())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback'); // Rollback is expected

        $this->sessionRepository->expects($this->exactly(2)) // Initial find, then find in errorHandler
            ->method('findByChatId')
            ->with($chatId)
            ->willReturnOnConsecutiveCalls($existingSession, $existingSession);

        // StateHandler will be called with MENU due to errorHandler
        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->expects($this->never()) // makeByState is NOT called if getNextState throws
            ->method('makeByState');

        $handlerResponseDTO = new StateHandlerResponseDTO(EnumMessageText::ERROR, []);
        $mockStateHandler->expects($this->never()) // handle is NOT called if getNextState throws
            ->method('handle');

        $this->sessionRepository->expects($this->once()) // Save in errorHandler after resetState
             ->method('save')
             ->with($this->callback(function (Session $session) {
                 return $session->getState() === EnumState::MENU && $session->getPayload() === null;
             }));

        $this->keyboardFactory->expects($this->once())->method('makeMenuKeyboard')->willReturn([]);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId && $message->getText() === EnumMessageText::ERROR->value;
            }));

        $this->manager->process($requestDTO);
    }
}
