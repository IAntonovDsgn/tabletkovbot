<?php

namespace Tests\Unit\Presentation\Console\Commands;

use App\Application\Services\DataSender\MessageBrokerInterface;
use App\Application\Services\Outbox\OutboxRelay;
use App\Application\Services\Outbox\OutboxRepositoryInterface;
use App\Domain\Entities\Message\Message;
use App\Presentation\Console\Commands\OutboxPublishCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\FakeLogger;

class OutboxPublishCommandTest extends TestCase
{
    private MockObject $outboxRepository;
    private MockObject $broker;
    private FakeLogger $logger;

    public function testRunRelaysPendingMessagesUntilStopped(): void
    {
        $this->outboxRepository = $this->createMock(OutboxRepositoryInterface::class);
        $this->broker = $this->createMock(MessageBrokerInterface::class);
        $this->logger = new FakeLogger();

        $relay = new OutboxRelay($this->outboxRepository, $this->broker, 10, 0);
        $command = new OutboxPublishCommand($relay, $this->logger);

        $pending = Message::restoreFromPersistence(9, 77, 'hello');
        $this->outboxRepository->method('getPendingMessages')
            ->willReturnOnConsecutiveCalls([$pending], []);

        $this->broker->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Message $message): bool {
                return $message->getId() === 9 && $message->getChatId() === 77;
            }))
            ->willReturnCallback(function () use ($relay): void {
                $relay->requestStop();
            });

        $this->outboxRepository->expects($this->once())
            ->method('delete')
            ->with($pending);

        $this->broker->expects($this->once())->method('close');

        $tester = new CommandTester($command);
        $statusCode = $tester->execute([]);

        self::assertSame(0, $statusCode);
        self::assertSame(0, $this->logger->countMessages('Failed to relay outbox message'));
    }
}
