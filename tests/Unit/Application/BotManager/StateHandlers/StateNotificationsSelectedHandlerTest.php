<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\StateHandlers\StateNotificationsSelectedHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\State\EnumState;
use PHPUnit\Framework\TestCase;

class StateNotificationsSelectedHandlerTest extends TestCase
{
    private StateNotificationsSelectedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new StateNotificationsSelectedHandler();
    }

    public function testHandleWhenNotificationsAreEnabled(): void
    {
        $chatId = 12345;
        $session = Session::create($chatId);
        $session->enableNotifications();

        $response = $this->handler->handle($session, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::NOTIFICATIONS_ENABLE,
            [
                new MessageButton(MessageButton::DISABLE_NOTIFICATIONS, EnumState::NOTIFICATION_DISABLED),
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleWhenNotificationsAreDisabled(): void
    {
        $chatId = 12345;
        $session = Session::create($chatId);
        $session->disableNotifications();

        $response = $this->handler->handle($session, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::NOTIFICATIONS_DISABLE,
            [
                new MessageButton(MessageButton::ENABLE_NOTIFICATIONS, EnumState::NOTIFICATION_ENABLED),
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
