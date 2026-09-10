<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\UseCases\StateNotificationDisabledHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use PHPUnit\Framework\TestCase;

class StateNotificationDisabledHandlerTest extends TestCase
{
    private KeyboardFactory $keyboardFactory;
    private StateNotificationDisabledHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateNotificationDisabledHandler(
            $this->keyboardFactory
        );
    }

    public function testHandleReturnsNotificationDisabled(): void
    {
        $session = Session::create(12345);
        $this->assertTrue($session->isNotificationEnabled());

        $response = $this->handler->handle($session, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED,
            $this->keyboardFactory->makeMenuKeyboard(),
        );

        $this->assertEquals($expectedResponse, $response);
        $this->assertFalse($session->isNotificationEnabled());
    }
}
