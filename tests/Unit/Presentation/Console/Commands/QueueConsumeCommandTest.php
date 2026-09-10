<?php

namespace Tests\Unit\Presentation\Console\Commands;

use App\Application\Services\SendDataService\SendDataService;
use App\Application\Services\SendDataService\DataTransportInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Infrastructure\RabbitMq\QueueConsumerInterface;
use App\Presentation\Console\Commands\QueueConsumeCommand;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class QueueConsumeCommandTest extends TestCase
{
    private MockObject $consumer;
    private MockObject $messageService;
    private CommandTester $tester;

    /** @var Closure(Message): void|null */
    private ?Closure $capturedCallback = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capturedCallback = null;
        $this->consumer = $this->createMock(QueueConsumerInterface::class);
        $this->messageService = $this->createMock(DataTransportInterface::class);

        $command = new QueueConsumeCommand($this->consumer, new SendDataService($this->messageService));
        $this->tester = new CommandTester($command);
    }

    private function runAndCaptureCallback(): Closure
    {
        $this->consumer->method('run')
            ->willReturnCallback(function (Closure $onMessage): void {
                $this->capturedCallback = $onMessage;
            });

        $statusCode = $this->tester->execute([]);

        self::assertSame(0, $statusCode);
        self::assertInstanceOf(Closure::class, $this->capturedCallback);

        return $this->capturedCallback;
    }

    public function testDeliversMessageTextToTelegramViaHandler(): void
    {
        $callback = $this->runAndCaptureCallback();

        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function (Message $message) {
                return $message->getChatId() === 55 && $message->getText() === 'ping';
            }));

        $callback(Message::create(55, 'ping'));
    }

    public function testDeliversMessageButtonsToTelegramViaHandler(): void
    {
        $callback = $this->runAndCaptureCallback();

        $button = new MessageButton('Menu', EnumState::MENU, 'extra');
        $this->messageService->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function (Message $message) use ($button) {
                $buttons = $message->getButtons();

                return count($buttons) === 1
                    && $buttons[0]->getTitle() === $button->getTitle()
                    && $buttons[0]->getNewState() === $button->getNewState()
                    && $buttons[0]->getAdditionalPayload() === $button->getAdditionalPayload();
            }));

        $callback(Message::create(55, 'choose', [$button]));
    }
}
