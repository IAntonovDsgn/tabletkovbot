<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateNotificationDisabledHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Exceptions\NotFoundEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateNotificationDisabledHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;

    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private KeyboardFactory $keyboardFactory;
    private StateNotificationDisabledHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateNotificationDisabledHandler(
            $this->keyboardFactory,
            $this->sessionRepository,
            $this->outboxRepository,
        );
    }

    public function testHandleDisablesNotifications(): void
    {
        $session = Session::create(self::CHAT_ID);
        $this->assertTrue($session->isNotificationEnabled());

        $this->sessionRepository->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($session);

        $this->sessionRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(fn(Session $updated): bool => !$updated->isNotificationEnabled()));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::SETTINGS_SAVED->value,
                $this->keyboardFactory->makeMenuKeyboard()
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID));

        $this->assertFalse($session->isNotificationEnabled());
    }

    public function testHandleThrowsExceptionIfSessionMissing(): void
    {
        $this->sessionRepository->method('findByChatId')->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::INTERNAL_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }
}
