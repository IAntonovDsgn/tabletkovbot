<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateChangeMedicamentSelectedMedicamentHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateChangeMedicamentSelectedMedicamentHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;

    private MockObject $medicamentRepository;
    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private StateChangeMedicamentSelectedMedicamentHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->handler = new StateChangeMedicamentSelectedMedicamentHandler(
            $this->medicamentRepository,
            $this->sessionRepository,
            $this->outboxRepository,
        );
    }

    /**
     * @throws NotFoundEntityException
     */
    public function testHandleStoresTheChosenMedicamentAndAsksWhatToChange(): void
    {
        $medicamentIdToChange = 2;
        $medicaments = [
            Medicament::restoreFromPersistence(1, 'Aspirin', self::CHAT_ID, new DateTimeImmutable(), true),
            Medicament::restoreFromPersistence(2, 'Ibuprofen', self::CHAT_ID, new DateTimeImmutable(), true),
        ];

        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($medicaments);

        $session = Session::create(self::CHAT_ID);
        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($session);

        $this->sessionRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(fn(Session $updated): bool => $updated->getPayload() === (string) $medicamentIdToChange));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::WHAT_YOU_WANT_TO_CHANGE->value,
                [
                    new MessageButton(MessageButton::CHANGE_NAME, EnumState::CHANGE_MEDICAMENT_NAME_SELECTED),
                    new MessageButton(MessageButton::CHANGE_NOTIFICATION_TIME, EnumState::CHANGE_NOTIFICATION_TIME_SELECTED),
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            ));

        $this->handler->handle(new RequestDTO(
            self::CHAT_ID,
            null,
            EnumState::SELECTED_MEDICAMENT_FOR_CHANGE->value . MessageButton::PAYLOAD_SEPARATOR . $medicamentIdToChange,
        ));

        $this->assertSame((string) $medicamentIdToChange, $session->getPayload());
    }

    public function testHandleThrowsExceptionIfPayloadMissing(): void
    {
        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    public function testHandleThrowsExceptionIfMedicamentNotFound(): void
    {
        $invalidMedicamentId = 999;
        $medicaments = [
            Medicament::restoreFromPersistence(1, 'Aspirin', self::CHAT_ID, new DateTimeImmutable(), true),
        ];

        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($medicaments);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(
            self::CHAT_ID,
            null,
            EnumState::SELECTED_MEDICAMENT_FOR_CHANGE->value . MessageButton::PAYLOAD_SEPARATOR . $invalidMedicamentId,
        ));
    }
}
