<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateMenuHandler;
use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateMenuHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;

    private MockObject $outboxRepository;
    private KeyboardFactory $keyboardFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
    }

    public function testHandleSendsMenuMessageWithKeyboard(): void
    {
        $handler = new StateMenuHandler($this->keyboardFactory, $this->outboxRepository);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with(Message::create(
                self::CHAT_ID,
                EnumMessageText::MENU->value,
                $this->keyboardFactory->makeMenuKeyboard()
            ));

        $handler->handle(new RequestDTO(self::CHAT_ID));
    }
}
