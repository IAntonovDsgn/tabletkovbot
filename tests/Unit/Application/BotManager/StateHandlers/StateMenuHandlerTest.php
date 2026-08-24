<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlers\StateMenuHandler;
use App\Application\Keyboard\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;
use PHPUnit\Framework\TestCase;

class StateMenuHandlerTest extends TestCase
{
    public function testHandleReturnsMenuMessageAndKeyboard(): void
    {
        $keyboardFactory = new KeyboardFactory();
        $handler = new StateMenuHandler($keyboardFactory);

        $chatId = 12345;
        $response = $handler->handle($chatId, null, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::MENU,
            $keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
