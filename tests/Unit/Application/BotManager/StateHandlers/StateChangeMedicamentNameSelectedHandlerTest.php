<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateChangeMedicamentNameSelectedHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use PHPUnit\Framework\TestCase;

class StateChangeMedicamentNameSelectedHandlerTest extends TestCase
{
    public function testHandleReturnsCorrectResponse(): void
    {
        $handler = new StateChangeMedicamentNameSelectedHandler();

        $response = $handler->handle(12345, null, '1', null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::ENTER_NEW_NAME,
            []
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
