<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\DTOs\StateHandlerResponseDTO;
use App\Application\BotManager\Factories\KeyboardFactory;
use App\Application\BotManager\StateHandlers\StateNotificationEnabledHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use PHPUnit\Framework\TestCase;

class StateNotificationEnabledHandlerTest extends TestCase
{
    private KeyboardFactory $keyboardFactory;
    private StateNotificationEnabledHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateNotificationEnabledHandler(
            $this->keyboardFactory
        );
    }

    public function testHandleReturnsNotificationEnabled(): void
    {
        $session = Session::create(12345, isNotificationEnable: false);
        $this->assertFalse($session->isNotificationEnabled());

        $response = $this->handler->handle($session, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED,
            $this->keyboardFactory->makeMenuKeyboard(),
        );

        $this->assertEquals($expectedResponse, $response);
        $this->assertTrue($session->isNotificationEnabled());
    }
}
