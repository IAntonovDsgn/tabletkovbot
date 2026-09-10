<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\UseCases\StateDeleteMedicamentSelectedHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
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
            Medicament::restoreFromPersistence(1, 'Aspirin', $chatId, new \DateTimeImmutable(), true),
            Medicament::restoreFromPersistence(2, 'Ibuprofen', $chatId, new \DateTimeImmutable(), true),
        ];

        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($medicaments);

        $session = Session::create($chatId);
        $response = $this->handler->handle($session, null, null);

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

        $session = Session::create($chatId);
        $this->handler->handle($session, null, null);
    }
}
