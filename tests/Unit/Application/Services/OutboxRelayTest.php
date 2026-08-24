<?php

namespace Tests\Unit\Application\Services;

use App\Application\Message\MessageBrokerInterface;
use App\Application\Outbox\OutboxRelay;
use App\Application\Outbox\OutboxRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OutboxRelayTest extends TestCase
{
    private MockObject $outboxRepository;
    private MockObject $broker;
    private MockObject $logger;
    private OutboxRelay $relay;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outboxRepository = $this->createMock(OutboxRepositoryInterface::class);
        $this->broker = $this->createMock(MessageBrokerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->relay = new OutboxRelay($this->outboxRepository, $this->broker, $this->logger, 10, 1000, 4);
    }

    private function makePendingMessage(int $id, int $chatId): Message
    {
        return Message::restoreFromPersistence($id, $chatId, EnumMessageText::MENU->value);
    }

    public function testProcessBatchPublishesAndDeletesEveryPendingMessage(): void
    {
        $first = $this->makePendingMessage(1, 111);
        $second = $this->makePendingMessage(2, 222);

        $this->outboxRepository->expects($this->once())
            ->method('getPendingMessages')
            ->with(10)
            ->willReturn([$first, $second]);

        // Successful delivery must not touch the attempts counter.
        $this->outboxRepository->expects($this->never())->method('markAttempt');

        $publishedChatIds = [];
        $deletedMessages = [];

        $this->broker->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (Message $message) use (&$publishedChatIds): void {
                $publishedChatIds[] = $message->getChatId();
            });

        $this->outboxRepository->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(function (Message $message) use (&$deletedMessages): void {
                $deletedMessages[] = $message->getId();
            });

        $this->assertTrue($this->relay->processBatch());
        $this->assertSame([111, 222], $publishedChatIds);
        $this->assertSame([1, 2], $deletedMessages);
    }

    public function testProcessBatchKeepsRowWhenPublishFails(): void
    {
        $message = $this->makePendingMessage(1, 111);

        $this->outboxRepository->method('getPendingMessages')->willReturn([$message]);

        $this->broker->expects($this->once())
            ->method('publish')
            ->willThrowException(new RuntimeException('broker is down'));

        $this->outboxRepository->expects($this->once())
            ->method('markAttempt')
            ->with($message)
            ->willReturn(1);

        // The row must stay pending below the attempts threshold.
        $this->outboxRepository->expects($this->never())->method('delete');

        $this->logger->expects($this->atLeastOnce())->method('error');

        // Nothing was relayed, so the caller should back off before retrying
        $this->assertFalse($this->relay->processBatch());
    }

    public function testProcessBatchDropsMessageAfterMaxAttemptsReached(): void
    {
        $relay = new OutboxRelay($this->outboxRepository, $this->broker, $this->logger, 10, 1000, 2);
        $message = $this->makePendingMessage(1, 111);

        $this->outboxRepository->method('getPendingMessages')->willReturn([$message]);

        $this->broker->method('publish')->willThrowException(new RuntimeException('broker is down'));

        $this->outboxRepository->expects($this->once())
            ->method('markAttempt')
            ->with($message)
            ->willReturn(2);

        $this->outboxRepository->expects($this->once())->method('delete')->with($message);

        /** @var list<array{string, array<string, mixed>}> $errors */
        $errors = [];
        $this->logger->method('error')->willReturnCallback(
            function (string $logMessage, array $context) use (&$errors): void {
                $errors[] = [$logMessage, $context];
            }
        );

        $this->assertFalse($relay->processBatch());

        $drops = array_values(array_filter(
            $errors,
            static fn (array $record): bool => $record[0] === 'Dropping outbox message after repeated failures'
        ));
        self::assertCount(1, $drops);
        self::assertSame(2, $drops[0][1]['attempts']);
        self::assertSame('broker is down', $drops[0][1]['last_error']);
    }

    public function testProcessBatchKeepsRowWhenMarkAttemptFails(): void
    {
        $message = $this->makePendingMessage(1, 111);

        $this->outboxRepository->method('getPendingMessages')->willReturn([$message]);

        $this->broker->method('publish')->willThrowException(new RuntimeException('broker is down'));

        $this->outboxRepository->expects($this->once())
            ->method('markAttempt')
            ->willThrowException(new RuntimeException('db is down'));

        // Counter update failed: the row must stay pending for the next cycle.
        $this->outboxRepository->expects($this->never())->method('delete');

        /** @var list<array{string, array<string, mixed>}> $warnings */
        $warnings = [];
        $this->logger->method('warning')->willReturnCallback(
            function (string $logMessage, array $context) use (&$warnings): void {
                $warnings[] = [$logMessage, $context];
            }
        );

        $this->assertFalse($this->relay->processBatch());

        $failures = array_values(array_filter(
            $warnings,
            static fn (array $record): bool => $record[0] === 'Failed to register outbox delivery attempt'
        ));
        self::assertCount(1, $failures);
        self::assertSame('db is down', $failures[0][1]['error']);
    }

    public function testProcessBatchDoesNotFailWhenDeleteFailsAfterConfirmedPublish(): void
    {
        $first = $this->makePendingMessage(1, 111);
        $second = $this->makePendingMessage(2, 222);

        $this->outboxRepository->method('getPendingMessages')->willReturn([$first, $second]);

        $this->broker->expects($this->exactly(2))->method('publish');

        $matcher = $this->exactly(2);
        $this->outboxRepository->expects($matcher)
            ->method('delete')
            ->willReturnCallback(function (Message $message) use ($matcher): void {
                if ($matcher->numberOfInvocations() === 1) {
                    throw new RuntimeException('DB hiccup');
                }
            });

        $this->logger->expects($this->atLeastOnce())->method('error');

        $this->assertTrue($this->relay->processBatch());
    }

    public function testProcessBatchReturnsFalseAndSkipsBrokerWhenOutboxIsEmpty(): void
    {
        $this->outboxRepository->expects($this->once())
            ->method('getPendingMessages')
            ->willReturn([]);

        $this->broker->expects($this->never())->method('publish');
        $this->outboxRepository->expects($this->never())->method('delete');

        $this->assertFalse($this->relay->processBatch());
    }

    public function testProcessBatchStopsMidBatchWhenStopIsRequested(): void
    {
        $messages = [
            $this->makePendingMessage(1, 111),
            $this->makePendingMessage(2, 222),
            $this->makePendingMessage(3, 333),
        ];

        $this->outboxRepository->method('getPendingMessages')->willReturn($messages);

        $published = 0;
        $this->broker->expects($this->once())->method('publish')
            ->willReturnCallback(function () use (&$published) {
                // Request stop during the first publish: the current message is
                // finished (delete included), then the batch must stop.
                $published++;
                $this->relay->requestStop();
            });

        $this->outboxRepository->expects($this->once())->method('delete');

        $this->assertTrue($this->relay->processBatch());
        $this->assertSame(1, $published);
    }
}
