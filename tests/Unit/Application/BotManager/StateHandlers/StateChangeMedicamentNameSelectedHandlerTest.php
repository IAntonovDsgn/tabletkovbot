<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateChangeMedicamentNameSelectedHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use PHPUnit\Framework\TestCase;

class StateChangeMedicamentNameSelectedHandlerTest extends TestCase
{
    public function testHandleReturnsCorrectResponse(): void
    {
        $handler = new StateChangeMedicamentNameSelectedHandler();

        // The handler is stateless and does not use any arguments, so they can be dummy values.
        $response = $handler->handle(12345, null, '1', null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::ENTER_NEW_NAME,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU)
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
