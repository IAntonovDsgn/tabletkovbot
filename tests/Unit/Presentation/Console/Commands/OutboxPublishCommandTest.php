<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Console\Commands;

use App\Application\Services\DataSender\MessageBrokerInterface;
use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\Services\Outbox\OutboxRelay;
use App\Application\Services\Outbox\ReportOutboxRepositoryInterface;
use App\Domain\Entities\Message\Message;
use App\Presentation\Console\Commands\OutboxPublishCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class OutboxPublishCommandTest extends TestCase
{
    private MockObject $messageOutboxRepository;
    private MockObject $reportOutboxRepository;
    private MockObject $broker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageOutboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->reportOutboxRepository = $this->createMock(ReportOutboxRepositoryInterface::class);
        $this->broker = $this->createMock(MessageBrokerInterface::class);
    }

    public function testRunRelaysPendingMessagesUntilStopped(): void
    {
        $relay = new OutboxRelay(
            $this->messageOutboxRepository,
            $this->reportOutboxRepository,
            $this->broker,
            $this->createMock(\Psr\Log\LoggerInterface::class),
            10,
            0,
            4,
        );
        $command = new OutboxPublishCommand($relay);

        $pending = Message::restoreFromPersistence(9, 77, 0, 'hello');
        $this->messageOutboxRepository->method('getMessages')
            ->willReturnOnConsecutiveCalls([$pending], []);
        $this->reportOutboxRepository->method('getReports')->willReturn([]);

        $this->broker->expects($this->once())
            ->method('publishMessage')
            ->with($this->callback(function (Message $message): bool {
                return $message->getId() === 9 && $message->getChatId() === 77;
            }))
            ->willReturnCallback(function () use ($relay): void {
                $relay->requestStop();
            });

        // The row only leaves the outbox after the broker confirmed.
        $this->messageOutboxRepository->expects($this->once())
            ->method('delete')
            ->with($pending);

        $this->broker->expects($this->once())->method('close');

        $tester = new CommandTester($command);

        self::assertSame(0, $tester->execute([]));
    }

    public function testCommandNameIsStable(): void
    {
        $relay = new OutboxRelay(
            $this->messageOutboxRepository,
            $this->reportOutboxRepository,
            $this->broker,
            $this->createMock(\Psr\Log\LoggerInterface::class),
            10,
            0,
            4,
        );

        self::assertSame('app:outbox-publish', (new OutboxPublishCommand($relay))->getName());
    }
}