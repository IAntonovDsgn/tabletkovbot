<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateDownloadReportSelectedHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use PHPUnit\Framework\TestCase;

class StateDownloadReportSelectedHandlerTest extends TestCase
{
    public function testHandleReturnsDatePrompt(): void
    {
        $handler = new StateDownloadReportSelectedHandler();

        $response = $handler->handle(12345, null, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::ENTER_DATE,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ]
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
