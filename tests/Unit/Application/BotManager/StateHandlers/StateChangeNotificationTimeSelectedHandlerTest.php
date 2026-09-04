<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateChangeNotificationTimeSelectedHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use PHPUnit\Framework\TestCase;

class StateChangeNotificationTimeSelectedHandlerTest extends TestCase
{
    public function testHandleReturnsCorrectResponse(): void
    {
        $handler = new StateChangeNotificationTimeSelectedHandler();

        $response = $handler->handle(12345, null, '1', null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::ENTER_TIME,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
