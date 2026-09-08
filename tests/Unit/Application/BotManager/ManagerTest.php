<?php

namespace Tests\Unit\Application\BotManager;

use App\Application\BotManager\DTOs\RequestDTO;
use App\Application\BotManager\DTOs\StateHandlerResponseDTO;
use App\Application\BotManager\Exceptions\InvalidValueException;
use App\Application\BotManager\Factories\StateHandlerFactory;
use App\Application\BotManager\Manager;
use App\Application\BotManager\StateHandlers\StateHandlerInterface;
use App\Application\Outbox\OutboxRepositoryInterface;
use App\Application\UnitOfWork\UnitOfWorkInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\TransitionStateNotAllowedException;
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
        $this->outboxRepository = $this->createMock(OutboxRepositoryInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWorkInterface::class);

        $this->manager = new Manager(
            $this->factoryStateHandler,
            $this->sessionRepository,
            $this->outboxRepository,
            $this->unitOfWork,
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

        $handlerResponseDTO = new StateHandlerResponseDTO(EnumMessageText::MENU, []);
        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(fn(Session $s) => $s->getChatId() === $chatId && $s->getState() === EnumState::MENU && $s->getPayload() === null),
                'start',
                null,
            )
            ->willReturn($handlerResponseDTO);

        $this->sessionRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Session $session) use ($chatId) {
                return $session->getChatId() === $chatId && $session->getState() === EnumState::MENU;
            }));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId && $message->getText() === EnumMessageText::MENU->value;
            }));

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

        $existingSession = Session::restoreFromPersistence(1, $chatId, true, EnumState::MENU);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->once())->method('commit');
        $this->unitOfWork->expects($this->never())->method('rollback');

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($existingSession);

        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->expects($this->once())
            ->method('makeByState')
            ->with($payloadState)
            ->willReturn($mockStateHandler);

        $handlerResponseDTO = new StateHandlerResponseDTO(EnumMessageText::ENTER_NEW_NAME, [], 'new_payload_data');
        $mockStateHandler->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(fn(Session $s) => $s->getChatId() === $chatId && $s->getState() === $payloadState),
                'some_text',
                null,
            )
            ->willReturn($handlerResponseDTO);

        $this->sessionRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Session $session) use ($chatId, $payloadState, $handlerResponseDTO) {
                return $session->getChatId() === $chatId
                       && $session->getState() === $payloadState
                       && $session->getPayload() === $handlerResponseDTO->newSessionPayload;
            }));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId, $handlerResponseDTO) {
                $messageText = $handlerResponseDTO->messageText;
                $messageTextValue = $messageText?->value;
                return $message->getChatId() === $chatId
                       && $message->getText() === $messageTextValue;
            }));

        $this->manager->process($requestDTO);
    }

    /**
     * @throws TransitionStateNotAllowedException
     * @throws Throwable
     */
    public function testProcessHandlesInvalidValueException(): void
    {
        $chatId = 123;
        $requestDTO = new RequestDTO($chatId, 'invalid_input', null);
        $existingSession = Session::create($chatId);

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
            ->willThrowException(new InvalidValueException('Error message'));

        $this->sessionRepository->expects($this->never())->method('insert');
        $this->sessionRepository->expects($this->never())->method('update');

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId && $message->getText() === 'Error message';
            }));

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
        $existingSession = Session::create($chatId);
        $expectedException = new class extends Exception {};

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

        $this->sessionRepository->expects($this->never())->method('insert');
        $this->sessionRepository->expects($this->never())->method('update');

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId
                    && $message->getText() === EnumMessageText::INTERNAL_ERROR->value;
            }));

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
        $existingSession = Session::create($chatId);
        $expectedException = new class extends Exception {};

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
        $existingSession = Session::create($chatId);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->never())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback');

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($existingSession);

        $mockStateHandler = $this->createMock(StateHandlerInterface::class);
        $this->factoryStateHandler->expects($this->never())
            ->method('makeByState');

        $mockStateHandler->expects($this->never())
            ->method('handle');

        $this->sessionRepository->expects($this->never())->method('update');

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId && $message->getText() === EnumMessageText::ERROR->value;
            }));

        $this->expectException(InvalidValueException::class);
        $this->manager->process($requestDTO);
    }
}
