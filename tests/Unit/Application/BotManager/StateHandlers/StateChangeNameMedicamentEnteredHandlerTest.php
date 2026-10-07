<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateChangeNameMedicamentEnteredHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Exceptions\NotFoundEntityException;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateChangeNameMedicamentEnteredHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;
    private const int MEDICAMENT_ID = 1;

    private MockObject $medicamentRepository;
    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private KeyboardFactory $keyboardFactory;
    private StateChangeNameMedicamentEnteredHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateChangeNameMedicamentEnteredHandler(
            $this->medicamentRepository,
            $this->keyboardFactory,
            $this->sessionRepository,
            $this->outboxRepository,
        );
    }

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function testHandleRenamesTheMedicament(): void
    {
        $newName = 'Paracetamol';

        $medicament = Medicament::restoreFromPersistence(
            self::MEDICAMENT_ID,
            'Aspirin',
            self::CHAT_ID,
            new DateTimeImmutable(),
            true
        );

        $this->givenSessionWithChosenMedicament();

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(self::MEDICAMENT_ID)
            ->willReturn($medicament);

        $this->medicamentRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(fn(Medicament $saved): bool => $saved->getName() === $newName));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::MEDICAMENT_RENAMED_SUCCESS->value,
                $this->keyboardFactory->makeMenuKeyboard()
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID, $newName));
    }

    /**
     * @throws NotFoundEntityException
     */
    public function testHandleThrowsExceptionOnNullName(): void
    {
        $medicament = Medicament::restoreFromPersistence(
            self::MEDICAMENT_ID,
            'Aspirin',
            self::CHAT_ID,
            new DateTimeImmutable(),
            true
        );

        $this->givenSessionWithChosenMedicament();

        $this->medicamentRepository->method('findById')->willReturn($medicament);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::INTERNAL_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    /**
     * @throws InvalidValueException
     */
    public function testHandleThrowsExceptionIfSessionMissing(): void
    {
        $this->sessionRepository->method('findByChatId')->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::INTERNAL_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID, 'New Name'));
    }

    /**
     * @throws InvalidValueException
     */
    public function testHandleThrowsExceptionIfMedicamentNotFound(): void
    {
        $this->givenSessionWithChosenMedicament();

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(self::MEDICAMENT_ID)
            ->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID, 'New Name'));
    }

    /**
     * @throws InvalidValueException
     */
    public function testHandleThrowsExceptionIfMedicamentBelongsToAnotherChat(): void
    {
        $medicament = Medicament::restoreFromPersistence(
            self::MEDICAMENT_ID,
            'Aspirin',
            54321,
            new DateTimeImmutable(),
            true
        );

        $this->givenSessionWithChosenMedicament();

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(self::MEDICAMENT_ID)
            ->willReturn($medicament);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID, 'New Name'));
    }

    private function givenSessionWithChosenMedicament(): void
    {
        $session = Session::create(self::CHAT_ID, payload: (string) self::MEDICAMENT_ID);

        $this->sessionRepository->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($session);
    }
}
