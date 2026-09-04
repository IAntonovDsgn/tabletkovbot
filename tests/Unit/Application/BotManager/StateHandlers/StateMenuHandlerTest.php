<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\KeyboardFactory;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlers\StateMenuHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use PHPUnit\Framework\TestCase;

class StateMenuHandlerTest extends TestCase
{
    public function testHandleReturnsMenuMessageAndKeyboard(): void
    {
        $keyboardFactory = new KeyboardFactory();
        $handler = new StateMenuHandler($keyboardFactory);

        $chatId = 12345;
        $response = $handler->handle(Session::create($chatId), null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::MENU,
            $keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
