<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateDeleteMedicamentSelectedHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use PHPUnit\Framework\TestCase;

class StateDeleteMedicamentSelectedHandlerTest extends TestCase
{
    private MedicamentRepositoryInterface $medicamentRepository;
    private StateDeleteMedicamentSelectedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->handler = new StateDeleteMedicamentSelectedHandler($this->medicamentRepository);
    }

    public function testHandleSuccessWithMedicaments(): void
    {
        $chatId = 12345;
        $medicaments = [
            new Medicament('Aspirin', $chatId, id: 1),
            new Medicament('Ibuprofen', $chatId, id: 2),
        ];

        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($medicaments);

        $response = $this->handler->handle($chatId, null, null, null);

        $expectedButtons = [
            new MessageButton('Aspirin', EnumState::SELECTED_MEDICAMENT_FOR_DELETE, '1'),
            new MessageButton('Ibuprofen', EnumState::SELECTED_MEDICAMENT_FOR_DELETE, '2'),
            new MessageButton(MessageButton::MENU, EnumState::MENU),
        ];
        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::CHOOSE_MEDICAMENT,
            $expectedButtons
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleThrowsExceptionWhenNoMedicamentsFound(): void
    {
        $chatId = 12345;

        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn([]);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle($chatId, null, null, null);
    }
}
