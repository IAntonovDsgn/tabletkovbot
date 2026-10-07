<?php

namespace Tests\Unit\Application\BotManager;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\Factories\StateHandlerFactory;
use App\Application\StateManager\Manager;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateHandlerInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\TransitionStateNotAllowedException;
use App\Domain\UnitOfWorkInterface;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class ManagerTest extends TestCase
{
    private MockObject $factoryStateHandler;
    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private MockObject $unitOfWork;
    private Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factoryStateHandler = $this->createMock(StateHandlerFactory::class);
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWorkInterface::class);

        $this->manager = new Manager(
            $this->factoryStateHandler,
            $this->sessionRepository,
            $this->outboxRepository,
            $this->unitOfWork,
            new KeyboardFactory(),
        );
    }

    /**
     * @throws TransitionStateNotAllowedException
     * @throws Throwable
     * @throws InvalidValueException
     */
    public function testProcessNewUserMenuRequest(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'start', null);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->once())->method('commit');
        $this->unitOfWork->expects($this->never())->method('rollback');

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn(null);

        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->expects($this->once())
            ->method('makeByState')
            ->with(EnumState::MENU)
            ->willReturn($mockStateHandler);

        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                fn(RequestDTO $params): bool
                    => $params->chatId === $chatId
                    && $params->messageText === 'start'
                    && $params->payload === null
            ));

        $this->sessionRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn(Session $session): bool
                    => $session->getChatId() === $chatId && $session->getState() === EnumState::MENU
            ));

        $this->outboxRepository->expects($this->never())->method('insert');

        $this->manager->process($requestDTO);
    }

    /**
     * @throws TransitionStateNotAllowedException
     * @throws Throwable
     * @throws InvalidValueException
     */
    public function testProcessExistingUserWithPayloadTransition(): void
    {
        $chatId = 123;
        $payloadState = EnumState::ADD_MEDICAMENT_SELECTED;
        $requestDTO = new RequestDTO($chatId, 'some_text', $payloadState->value);

        $existingSession = $this->givenExistingSession($chatId, EnumState::MENU);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->once())->method('commit');
        $this->unitOfWork->expects($this->never())->method('rollback');

        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->expects($this->once())
            ->method('makeByState')
            ->with($payloadState)
            ->willReturn($mockStateHandler);

        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                fn(RequestDTO $params): bool
                    => $params->chatId === $chatId
                    && $params->messageText === 'some_text'
                    && $params->payload === $payloadState->value
            ));

        $this->sessionRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(
                fn(Session $session): bool
                    => $session->getChatId() === $chatId && $session->getState() === $payloadState
            ));

        $this->manager->process($requestDTO);

        $this->assertSame($payloadState, $existingSession->getState());
    }

    /**
     * @throws TransitionStateNotAllowedException
     * @throws Throwable
     */
    public function testProcessHandlesInvalidValueException(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'invalid_input', null);
        $this->givenExistingSession($chatId, EnumState::MENU);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->never())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback');

        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->method('makeByState')->willReturn($mockStateHandler);

        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->willThrowException(new InvalidValueException('Error message'));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn(Message $message): bool
                    => $message->getChatId() === $chatId && $message->getText() === 'Error message'
            ));

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Error message');
        $this->manager->process($requestDTO);
    }

    /**
     * @throws TransitionStateNotAllowedException
     * @throws Throwable
     * @throws InvalidValueException
     */
    public function testProcessHandlesGeneralThrowable(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'error', null);
        $this->givenExistingSession($chatId, EnumState::MENU);
        $expectedException = new class extends Exception {
        };

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->never())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback');

        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->method('makeByState')->willReturn($mockStateHandler);

        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->willThrowException($expectedException);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn(Message $message): bool
                    => $message->getChatId() === $chatId
                    && $message->getText() === EnumMessageText::INTERNAL_ERROR->value
            ));

        $this->expectException(get_class($expectedException));
        $this->manager->process($requestDTO);
    }

    /**
     * @throws TransitionStateNotAllowedException
     * @throws Throwable
     * @throws InvalidValueException
     */
    public function testProcessStillThrowsOriginalExceptionWhenNotificationFails(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'error', null);
        $this->givenExistingSession($chatId, EnumState::MENU);
        $expectedException = new class extends Exception {
        };

        $this->unitOfWork->expects($this->once())->method('rollback');

        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->method('makeByState')->willReturn($mockStateHandler);

        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->willThrowException($expectedException);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->willThrowException(new RuntimeException('DB is down'));

        $this->expectException(get_class($expectedException));
        $this->manager->process($requestDTO);
    }

    /**
     * @throws Throwable
     * @throws TransitionStateNotAllowedException
     */
    public function testGetNextStateThrowsInvalidValueExceptionForInvalidPayload(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'text', 'INVALID_STATE');
        $this->givenExistingSession($chatId, EnumState::MENU);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->never())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback');

        $this->factoryStateHandler->expects($this->never())
            ->method('makeByState');

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn(Message $message): bool
                    => $message->getChatId() === $chatId && $message->getText() === EnumMessageText::ERROR->value
            ));

        $this->expectException(InvalidValueException::class);
        $this->manager->process($requestDTO);
    }

    private function givenExistingSession(int $chatId, EnumState $state): Session
    {
        $session = Session::restoreFromPersistence(1, $chatId, true, $state);

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($session);

        return $session;
    }
}
