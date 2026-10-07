<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateNotifiedHandler;
use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateNotifiedHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;
    private const int MEDICAMENT_ID = 1;

    private MockObject $medicamentRepository;
    private MockObject $intakeMarkRepository;
    private MockObject $outboxRepository;
    private KeyboardFactory $keyboardFactory;
    private StateNotifiedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->intakeMarkRepository = $this->createMock(IntakeMarkRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateNotifiedHandler(
            $this->medicamentRepository,
            $this->intakeMarkRepository,
            $this->keyboardFactory,
            $this->outboxRepository,
        );
    }

    /**
     * @throws NotFoundEntityException
     */
    public function testHandleSavesTheIntakeMark(): void
    {
        $medicament = $this->givenOwnMedicament();

        $this->intakeMarkRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn(IntakeMark $mark): bool
                    => $mark->getChatId() === self::CHAT_ID
                    && $mark->getMedicamentId() === self::MEDICAMENT_ID
            ));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::INTAKE_MARK_SAVED->value,
                $this->keyboardFactory->makeMenuKeyboard()
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID, (string) self::MEDICAMENT_ID));

        $this->assertNotNull($medicament);
    }

    /**
     * @throws NotFoundEntityException
     */
    public function testHandleSavesTheIntakeMarkWhenMedicamentIdComesFromButtonPayload(): void
    {
        $this->givenOwnMedicament();

        $this->intakeMarkRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn(IntakeMark $mark): bool
                    => $mark->getChatId() === self::CHAT_ID
                    && $mark->getMedicamentId() === self::MEDICAMENT_ID
            ));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::INTAKE_MARK_SAVED->value,
                $this->keyboardFactory->makeMenuKeyboard()
            ));

        $this->handler->handle(new RequestDTO(
            self::CHAT_ID,
            null,
            EnumState::NOTIFIED->value . MessageButton::PAYLOAD_SEPARATOR . self::MEDICAMENT_ID,
        ));
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

        $this->handler->handle(new RequestDTO(self::CHAT_ID, (string) $medicamentId));
    }

    public function testHandleThrowsExceptionIfMedicamentBelongsToAnotherChat(): void
    {
        $medicament = Medicament::restoreFromPersistence(
            self::MEDICAMENT_ID,
            'Aspirin',
            54321,
            new DateTimeImmutable(),
            true
        );

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(self::MEDICAMENT_ID)
            ->willReturn($medicament);

        $this->intakeMarkRepository->expects($this->never())->method('insert');

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID, (string) self::MEDICAMENT_ID));
    }

    public function testHandleThrowsExceptionIfTextIsNull(): void
    {
        $this->medicamentRepository->expects($this->never())->method('findById');
        $this->intakeMarkRepository->expects($this->never())->method('insert');

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    private function givenOwnMedicament(): Medicament
    {
        $medicament = Medicament::restoreFromPersistence(
            self::MEDICAMENT_ID,
            'Aspirin',
            self::CHAT_ID,
            new DateTimeImmutable(),
            true
        );

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(self::MEDICAMENT_ID)
            ->willReturn($medicament);

        return $medicament;
    }
}
