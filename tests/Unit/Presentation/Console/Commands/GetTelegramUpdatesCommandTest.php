<?php

namespace Tests\Unit\Presentation\Console\Commands;

use App\Application\BotManager\RequestDTO;
use App\Application\Message\Services\MessageServiceInterface;
use App\Presentation\Console\Commands\GetTelegramUpdatesCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class GetTelegramUpdatesCommandTest extends TestCase
{
    private MockObject $messageService;
    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageService = $this->createMock(MessageServiceInterface::class);
        $this->tester = new CommandTester(new GetTelegramUpdatesCommand($this->messageService));
    }

    public function testPrintsNoUpdatesWhenQueueIsEmpty(): void
    {
        $this->messageService->expects($this->once())
            ->method('getUpdates')
            ->willReturn([]);

        $statusCode = $this->tester->execute([]);

        self::assertSame(0, $statusCode);
        self::assertStringContainsString('No updates', $this->tester->getDisplay());
    }

    public function testPrintsEveryUpdateChatIdAndText(): void
    {
        $this->messageService->expects($this->once())
            ->method('getUpdates')
            ->willReturn([
                new RequestDTO(111, 'Hello'),
                new RequestDTO(222, null),
            ]);

        $statusCode = $this->tester->execute([]);

        $display = $this->tester->getDisplay();
        self::assertSame(0, $statusCode);
        self::assertStringContainsString('chat_id: 111, text: Hello', $display);
        self::assertStringContainsString('chat_id: 222, text: ', $display);
    }

    public function testReturnsFailureWhenServiceThrows(): void
    {
        $this->messageService->expects($this->once())
            ->method('getUpdates')
            ->willThrowException(new RuntimeException('api down'));

        $statusCode = $this->tester->execute([]);

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Failed: api down', $this->tester->getDisplay());
    }
}
