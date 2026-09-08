<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\StateHandlers\StateIntakeMarkHasMadeHandler;
use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use App\Domain\Exceptions\NotFoundEntityException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StateIntakeMarkHasMadeHandlerTest extends TestCase
{
    private MedicamentRepositoryInterface $medicamentRepository;
    private IntakeMarkRepositoryInterface $intakeMarkRepository;
    private KeyboardFactory $keyboardFactory;
    private StateIntakeMarkHasMadeHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->intakeMarkRepository = $this->createMock(IntakeMarkRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateIntakeMarkHasMadeHandler(
            $this->medicamentRepository,
            $this->intakeMarkRepository,
            $this->keyboardFactory
        );
    }

    /**
     * @throws NotFoundEntityException
     */
    public function testHandleSuccess(): void
    {
        $chatId = 12345;
        $medicamentId = 1;

        $medicament = Medicament::restoreFromPersistence(
            $medicamentId,
            'Aspirin',
            $chatId,
            new DateTimeImmutable(),
            true
        );

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with($medicamentId)
            ->willReturn($medicament);

        $this->intakeMarkRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (IntakeMark $intakeMark) use ($chatId, $medicamentId) {
                return $intakeMark->getChatId() === $chatId && $intakeMark->getMedicamentId() === $medicamentId;
            }));

        $response = $this->handler->handle(Session::create($chatId), null, (string) $medicamentId);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::INTAKE_MARK_SAVED,
            $this->keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleThrowsExceptionIfMedicamentNotFound(): void
    {
        $medicamentId = 999;
        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with($medicamentId)
            ->willReturn(null);

        $this->intakeMarkRepository->expects($this->never())->method('insert');

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(Session::create(12345), null, (string) $medicamentId);
    }

    public function testHandleThrowsExceptionIfMedicamentBelongsToAnotherChat(): void
    {
        $chatId = 12345;
        $anotherChatId = 54321;
        $medicamentId = 1;

        $medicament = Medicament::restoreFromPersistence(
            $medicamentId,
            'Aspirin',
            $anotherChatId,
            new DateTimeImmutable(),
            true
        );

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with($medicamentId)
            ->willReturn($medicament);

        $this->intakeMarkRepository->expects($this->never())->method('insert');

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(Session::create($chatId), null, (string) $medicamentId);
    }
}
