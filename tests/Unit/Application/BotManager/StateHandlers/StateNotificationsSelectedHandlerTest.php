<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateNotificationsSelectedHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use PHPUnit\Framework\TestCase;

class StateNotificationsSelectedHandlerTest extends TestCase
{
    private SessionRepositoryInterface $sessionRepository;
    private StateNotificationsSelectedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->handler = new StateNotificationsSelectedHandler($this->sessionRepository);
    }

    public function testHandleWhenNotificationsAreEnabled(): void
    {
        $chatId = 12345;
        $session = Session::create($chatId);
        $session->enableNotifications();

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($session);

        $response = $this->handler->handle($chatId, null, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::NOTIFICATIONS_ENABLE,
            [
                new MessageButton(MessageButton::DISABLE_NOTIFICATIONS, EnumState::NOTIFICATION_DISABLED)
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleWhenNotificationsAreDisabled(): void
    {
        $chatId = 12345;
        $session = Session::create($chatId);
        $session->disableNotifications();

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($session);

        $response = $this->handler->handle($chatId, null, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::NOTIFICATIONS_DISABLE,
            [
                new MessageButton(MessageButton::ENABLE_NOTIFICATIONS, EnumState::NOTIFICATION_ENABLED)
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleWhenNoSessionExists(): void
    {
        $chatId = 12345;

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn(null);

        // Should default to enabled
        $response = $this->handler->handle($chatId, null, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::NOTIFICATIONS_ENABLE,
            [
                new MessageButton(MessageButton::DISABLE_NOTIFICATIONS, EnumState::NOTIFICATION_DISABLED)
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
