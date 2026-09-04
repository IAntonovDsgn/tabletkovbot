<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateSelectedMedicamentForDeleteHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use PHPUnit\Framework\TestCase;

class StateSelectedMedicamentForDeleteHandlerTest extends TestCase
{
    public function testHandleReturnsConfirmationPromptAndForwardsPayload(): void
    {
        $handler = new StateSelectedMedicamentForDeleteHandler();
        $medicamentId = '42';

        $response = $handler->handle(12345, null, null, $medicamentId);

        $expectedButtons = [
            new MessageButton(MessageButton::CONFIRM, EnumState::DELETE_MEDICAMENT_CONFIRMED),
            new MessageButton(MessageButton::CANCEL, EnumState::MENU),
        ];

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::ARE_YOU_CONFIRM_DELETE_MEDICAMENT,
            $expectedButtons,
            $medicamentId
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
