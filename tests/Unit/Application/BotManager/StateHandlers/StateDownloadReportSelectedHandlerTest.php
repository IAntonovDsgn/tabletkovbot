<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateDownloadReportSelectedHandler;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateDownloadReportSelectedHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;

    private MockObject $outboxRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
    }

    public function testHandleAsksForTheStartDate(): void
    {
        $handler = new StateDownloadReportSelectedHandler($this->outboxRepository);

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->expectedMessage());

        $handler->handle(new RequestDTO(self::CHAT_ID, 'Скачать отчет'));
    }

    private function expectedMessage(): Message
    {
        return Message::create(
            self::CHAT_ID,
            EnumMessageText::ENTER_DATE->value,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ]
        );
    }
}
