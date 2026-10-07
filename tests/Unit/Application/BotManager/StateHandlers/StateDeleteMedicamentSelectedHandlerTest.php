<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateDeleteMedicamentSelectedHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateDeleteMedicamentSelectedHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;

    private MockObject $medicamentRepository;
    private MockObject $outboxRepository;
    private StateDeleteMedicamentSelectedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->handler = new StateDeleteMedicamentSelectedHandler(
            $this->medicamentRepository,
            $this->outboxRepository,
        );
    }

    public function testHandleOffersActiveMedicamentsForDeletion(): void
    {
        $medicaments = [
            Medicament::restoreFromPersistence(1, 'Aspirin', self::CHAT_ID, new \DateTimeImmutable(), true),
            Medicament::restoreFromPersistence(2, 'Ibuprofen', self::CHAT_ID, new \DateTimeImmutable(), true),
        ];

        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($medicaments);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::CHOOSE_MEDICAMENT->value,
                [
                    new MessageButton('Aspirin', EnumState::SELECTED_MEDICAMENT_FOR_DELETE, '1'),
                    new MessageButton('Ibuprofen', EnumState::SELECTED_MEDICAMENT_FOR_DELETE, '2'),
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    public function testHandleThrowsExceptionWhenNoMedicamentsFound(): void
    {
        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn([]);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }
}
