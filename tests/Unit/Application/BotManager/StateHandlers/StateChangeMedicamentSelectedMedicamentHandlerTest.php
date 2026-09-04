<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlers\StateChangeMedicamentSelectedMedicamentHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StateChangeMedicamentSelectedMedicamentHandlerTest extends TestCase
{
    private MedicamentRepositoryInterface $medicamentRepository;
    private StateChangeMedicamentSelectedMedicamentHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->handler = new StateChangeMedicamentSelectedMedicamentHandler($this->medicamentRepository);
    }

    /**
     * @throws NotFoundEntityException
     */
    public function testHandleSuccess(): void
    {
        $chatId = 12345;
        $medicamentIdToChange = 2;
        $medicaments = [
            Medicament::restoreFromPersistence(1, 'Aspirin', $chatId, new DateTimeImmutable(), true),
            Medicament::restoreFromPersistence(2, 'Ibuprofen', $chatId, new DateTimeImmutable(), true),
        ];

        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($medicaments);

        $session = Session::create($chatId);
        $response = $this->handler->handle($session, null, (string) $medicamentIdToChange);

        $expectedButtons = [
            new MessageButton(MessageButton::CHANGE_NAME, EnumState::CHANGE_MEDICAMENT_NAME_SELECTED),
            new MessageButton(MessageButton::CHANGE_NOTIFICATION_TIME, EnumState::CHANGE_NOTIFICATION_TIME_SELECTED),
            new MessageButton(MessageButton::MENU, EnumState::MENU),
        ];
        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::WHAT_YOU_WANT_TO_CHANGE,
            $expectedButtons,
            (string) $medicamentIdToChange
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleThrowsExceptionIfMedicamentNotFound(): void
    {
        $chatId = 12345;
        $invalidMedicamentId = 999;
        $medicaments = [
            Medicament::restoreFromPersistence(1, 'Aspirin', $chatId, new DateTimeImmutable(), true),
        ];

        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($medicaments);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $session = Session::create($chatId);
        $this->handler->handle($session, null, (string) $invalidMedicamentId);
    }
}
