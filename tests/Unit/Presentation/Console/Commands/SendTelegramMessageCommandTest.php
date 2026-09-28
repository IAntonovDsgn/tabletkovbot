<?php

namespace Tests\Unit\Presentation\Console\Commands;

use App\Application\Services\Sender\Sender;
use App\Application\Services\Sender\DataTransportInterface;
use App\Domain\Entities\Message\Message;
use App\Infrastructure\TelegramMessageTransport\SendMessageException;
use App\Presentation\Console\Commands\SendTelegramMessageCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendTelegramMessageCommandTest extends TestCase
{
    private MockObject $messageService;
    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageService = $this->createMock(DataTransportInterface::class);
        $command = new SendTelegramMessageCommand(new Sender($this->messageService));
        $this->tester = new CommandTester($command);
    }

    public function testSendsMessageWithNumericChatId(): void
    {
        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function (Message $message) {
                return $message->getChatId() === 123 && $message->getText() === 'hello';
            }));

        $statusCode = $this->tester->execute(['chat_id' => '123', 'message' => 'hello']);

        self::assertSame(0, $statusCode);
    }

    public function testRejectsNonNumericChatIdWithoutSending(): void
    {
        $this->messageService->expects($this->never())->method('sendMessage');

        $statusCode = $this->tester->execute(['chat_id' => 'abc', 'message' => 'hello']);

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('chat_id must be numeric', $this->tester->getDisplay());
    }

    public function testReturnsFailureWhenHandlerThrows(): void
    {
        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(new SendMessageException('Bad Request: chat not found'));

        $statusCode = $this->tester->execute(['chat_id' => '123', 'message' => 'hello']);

        self::assertSame(1, $statusCode);
        self::assertStringContainsString(
            'Failed to send message: Bad Request: chat not found',
            $this->tester->getDisplay()
        );
    }
}
