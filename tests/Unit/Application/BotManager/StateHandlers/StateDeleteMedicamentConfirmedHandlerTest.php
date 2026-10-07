<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateDeleteMedicamentConfirmedHandler;
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

class StateDeleteMedicamentConfirmedHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;
    private const int MEDICAMENT_ID = 1;

    private MockObject $medicamentRepository;
    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private KeyboardFactory $keyboardFactory;
    private StateDeleteMedicamentConfirmedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateDeleteMedicamentConfirmedHandler(
            $this->medicamentRepository,
            $this->keyboardFactory,
            $this->sessionRepository,
            $this->outboxRepository,
        );
    }

    /**
     * @throws NotFoundEntityException
     */
    public function testHandleDeactivatesTheMedicament(): void
    {
        $medicament = Medicament::restoreFromPersistence(
            self::MEDICAMENT_ID,
            'Aspirin',
            self::CHAT_ID,
            new DateTimeImmutable(),
            true
        );

        $this->assertTrue($medicament->isActive());

        $this->givenSessionWithChosenMedicament();

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(self::MEDICAMENT_ID)
            ->willReturn($medicament);

        $this->medicamentRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(fn(Medicament $saved): bool => !$saved->isActive()));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::MEDICAMENT_DELETED->value,
                $this->keyboardFactory->makeMenuKeyboard()
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    public function testHandleThrowsExceptionIfSessionMissing(): void
    {
        $this->sessionRepository->method('findByChatId')->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::INTERNAL_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    public function testHandleThrowsExceptionIfMedicamentNotFound(): void
    {
        $this->givenSessionWithChosenMedicament();

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(self::MEDICAMENT_ID)
            ->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
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

        $this->givenSessionWithChosenMedicament();

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(self::MEDICAMENT_ID)
            ->willReturn($medicament);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    private function givenSessionWithChosenMedicament(): void
    {
        $session = Session::create(self::CHAT_ID, payload: (string) self::MEDICAMENT_ID);

        $this->sessionRepository->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($session);
    }
}
