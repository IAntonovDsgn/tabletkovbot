<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateAddMedicamentSelectedHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateAddMedicamentSelectedHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;

    private MockObject $outboxRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
    }

    public function testHandleAsksForTheNewName(): void
    {
        $handler = new StateAddMedicamentSelectedHandler($this->outboxRepository);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::ENTER_NEW_NAME->value,
                [
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            ));

        $handler->handle(new RequestDTO(self::CHAT_ID));
    }
}
