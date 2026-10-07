<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateNotificationsSelectedHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateNotificationsSelectedHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;

    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private StateNotificationsSelectedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->handler = new StateNotificationsSelectedHandler(
            $this->sessionRepository,
            $this->outboxRepository,
        );
    }

    public function testHandleWhenNotificationsAreEnabled(): void
    {
        $session = Session::create(self::CHAT_ID);

        $this->sessionRepository->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($session);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::NOTIFICATIONS_ENABLE->value,
                [
                    new MessageButton(MessageButton::DISABLE_NOTIFICATIONS, EnumState::NOTIFICATION_DISABLED),
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    public function testHandleWhenNotificationsAreDisabled(): void
    {
        $session = Session::create(self::CHAT_ID, isNotificationEnable: false);

        $this->sessionRepository->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($session);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::NOTIFICATIONS_DISABLE->value,
                [
                    new MessageButton(MessageButton::ENABLE_NOTIFICATIONS, EnumState::NOTIFICATION_ENABLED),
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    public function testHandleThrowsExceptionIfSessionMissing(): void
    {
        $this->sessionRepository->method('findByChatId')->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::INTERNAL_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }
}
