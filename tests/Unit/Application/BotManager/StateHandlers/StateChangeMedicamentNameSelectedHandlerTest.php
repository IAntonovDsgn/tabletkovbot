<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\StateHandlers\StateChangeMedicamentNameSelectedHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use PHPUnit\Framework\TestCase;

class StateChangeMedicamentNameSelectedHandlerTest extends TestCase
{
    public function testHandleReturnsCorrectResponse(): void
    {
        $handler = new StateChangeMedicamentNameSelectedHandler();

        $session = Session::create(12345, payload: '1');
        $response = $handler->handle($session, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::ENTER_NEW_NAME,
            []
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
