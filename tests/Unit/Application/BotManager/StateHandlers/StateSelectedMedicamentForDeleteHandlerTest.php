<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateSelectedMedicamentForDeleteHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateSelectedMedicamentForDeleteHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;
    private const string MEDICAMENT_ID = '42';

    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private StateSelectedMedicamentForDeleteHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->handler = new StateSelectedMedicamentForDeleteHandler(
            $this->sessionRepository,
            $this->outboxRepository,
        );
    }

    public function testHandleStoresPayloadAndAsksForConfirmation(): void
    {
        $session = Session::create(self::CHAT_ID);

        $this->sessionRepository->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn($session);

        $this->sessionRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(
                fn(Session $updated): bool => $updated->getPayload() === self::MEDICAMENT_ID
            ));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::ARE_YOU_CONFIRM_DELETE_MEDICAMENT->value,
                [
                    new MessageButton(MessageButton::CONFIRM, EnumState::DELETE_MEDICAMENT_CONFIRMED),
                    new MessageButton(MessageButton::CANCEL, EnumState::MENU),
                ]
            ));

        $this->handler->handle(new RequestDTO(
            self::CHAT_ID,
            null,
            EnumState::SELECTED_MEDICAMENT_FOR_DELETE->value . MessageButton::PAYLOAD_SEPARATOR . self::MEDICAMENT_ID,
        ));

        $this->assertSame(self::MEDICAMENT_ID, $session->getPayload());
    }

    public function testHandleThrowsExceptionIfPayloadMissing(): void
    {
        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    public function testHandleThrowsExceptionIfSessionMissing(): void
    {
        $this->sessionRepository->method('findByChatId')->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::INTERNAL_ERROR->value);

        $this->handler->handle(new RequestDTO(
            self::CHAT_ID,
            null,
            EnumState::SELECTED_MEDICAMENT_FOR_DELETE->value . MessageButton::PAYLOAD_SEPARATOR . self::MEDICAMENT_ID,
        ));
    }
}
