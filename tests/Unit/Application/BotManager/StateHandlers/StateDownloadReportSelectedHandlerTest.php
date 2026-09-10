<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\UseCases\StateDownloadReportSelectedHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\States\EnumState;
use PHPUnit\Framework\TestCase;

class StateDownloadReportSelectedHandlerTest extends TestCase
{
    public function testHandleReturnsDatePrompt(): void
    {
        $handler = new StateDownloadReportSelectedHandler();

        $session = Session::create(12345);
        $response = $handler->handle($session, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::ENTER_DATE,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
