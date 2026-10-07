<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateMedicamentNameEnteredHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateMedicamentNameEnteredHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;
    private const int NEW_MEDICAMENT_ID = 1;

    private MockObject $medicamentRepository;
    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private StateMedicamentNameEnteredHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->handler = new StateMedicamentNameEnteredHandler(
            $this->medicamentRepository,
            $this->sessionRepository,
            $this->outboxRepository,
        );
    }

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function testHandleCreatesTheMedicamentAndAsksForTheTime(): void
    {
        $medicamentName = 'Aspirin';

        $this->medicamentRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn(Medicament $medicament): bool
                    => $medicament->getName() === $medicamentName
                    && $medicament->getChatId() === self::CHAT_ID
            ))
            ->willReturn(self::NEW_MEDICAMENT_ID);

        $session = Session::create(self::CHAT_ID);
        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($session);

        $this->sessionRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(
                fn(Session $updated): bool => $updated->getPayload() === (string) self::NEW_MEDICAMENT_ID
            ));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::ENTER_TIME->value,
                [
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID, $medicamentName));

        $this->assertSame((string) self::NEW_MEDICAMENT_ID, $session->getPayload());
    }

    public function testHandleThrowsExceptionOnNullText(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::INTERNAL_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    public function testHandleThrowsExceptionIfSessionMissing(): void
    {
        $this->medicamentRepository->method('insert')->willReturn(self::NEW_MEDICAMENT_ID);
        $this->sessionRepository->method('findByChatId')->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::INTERNAL_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID, 'Aspirin'));
    }
}
