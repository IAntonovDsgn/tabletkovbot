<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\KeyboardFactory;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlers\StateMakeIntakeMarkSelectedHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use PHPUnit\Framework\TestCase;

class StateMakeIntakeMarkSelectedHandlerTest extends TestCase
{
    private MedicamentRepositoryInterface $medicamentRepository;
    private StateMakeIntakeMarkSelectedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->handler = new StateMakeIntakeMarkSelectedHandler(
            $this->medicamentRepository,
            new KeyboardFactory()
        );
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

        $response = $this->handler->handle(Session::create($chatId), null, null);

        $expectedButtons = [
            new MessageButton('Aspirin', EnumState::INTAKE_MARK_HAS_MADE, '1'),
            new MessageButton('Ibuprofen', EnumState::INTAKE_MARK_HAS_MADE, '2'),
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

        $this->handler->handle(Session::create($chatId), null, null);
    }
}
