<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateAddMedicamentSelectedHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use PHPUnit\Framework\TestCase;

class StateAddMedicamentSelectedHandlerTest extends TestCase
{
    public function testHandleReturnsCorrectResponse(): void
    {
        $handler = new StateAddMedicamentSelectedHandler();

        $chatId = 12345;
        $response = $handler->handle($chatId, null, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::ENTER_NEW_NAME,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
