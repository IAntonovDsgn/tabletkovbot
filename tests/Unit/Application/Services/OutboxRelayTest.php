<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\SendDataService\MessageBrokerInterface;
use App\Application\Services\OutboxService\OutboxRelay;
use App\Application\Services\OutboxService\OutboxRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class OutboxRelayTest extends TestCase
{
    private MockObject $outboxRepository;
    private MockObject $broker;
    private OutboxRelay $relay;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outboxRepository = $this->createMock(OutboxRepositoryInterface::class);
        $this->broker = $this->createMock(MessageBrokerInterface::class);
        $this->relay = new OutboxRelay($this->outboxRepository, $this->broker, 10, 1000, 4);
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

        $this->outboxRepository->expects($this->never())->method('delete');

        /** @var list<array{Throwable, array<string, mixed>}> $errors */
        $errors = [];
        $onError = function (Throwable $e, array $context) use (&$errors): void {
            $errors[] = [$e, $context];
        };

        $this->assertFalse($this->relay->processBatch($onError));
        $this->assertNotEmpty($errors);
    }

    public function testProcessBatchDropsMessageAfterMaxAttemptsReached(): void
    {
        $relay = new OutboxRelay($this->outboxRepository, $this->broker, 10, 1000, 2);
        $message = $this->makePendingMessage(1, 111);

        $this->outboxRepository->method('getPendingMessages')->willReturn([$message]);

        $this->broker->method('publish')->willThrowException(new RuntimeException('broker is down'));

        $this->outboxRepository->expects($this->once())
            ->method('markAttempt')
            ->with($message)
            ->willReturn(2);

        $this->outboxRepository->expects($this->once())->method('delete')->with($message);

        /** @var list<array{Throwable, array<string, mixed>}> $errors */
        $errors = [];
        $onError = function (Throwable $e, array $context) use (&$errors): void {
            $errors[] = [$e, $context];
        };

        $this->assertFalse($relay->processBatch($onError));

        $drops = array_values(array_filter(
            $errors,
            static fn(array $record): bool => $record[1]['phase'] === 'drop_after_max_attempts'
        ));
        self::assertCount(1, $drops);
        self::assertSame(2, $drops[0][1]['attempts']);
        self::assertSame('broker is down', $drops[0][0]->getMessage());
    }

    public function testProcessBatchKeepsRowWhenMarkAttemptFails(): void
    {
        $message = $this->makePendingMessage(1, 111);

        $this->outboxRepository->method('getPendingMessages')->willReturn([$message]);

        $this->broker->method('publish')->willThrowException(new RuntimeException('broker is down'));

        $this->outboxRepository->expects($this->once())
            ->method('markAttempt')
            ->willThrowException(new RuntimeException('db is down'));

        $this->outboxRepository->expects($this->never())->method('delete');

        /** @var list<array{Throwable, array<string, mixed>}> $warnings */
        $warnings = [];
        $onError = function (Throwable $e, array $context) use (&$warnings): void {
            $warnings[] = [$e, $context];
        };

        $this->assertFalse($this->relay->processBatch($onError));

        $failures = array_values(array_filter(
            $warnings,
            static fn(array $record): bool => $record[1]['phase'] === 'register_attempt'
        ));
        self::assertCount(1, $failures);
        self::assertSame('db is down', $failures[0][0]->getMessage());
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

        /** @var list<array{Throwable, array<string, mixed>}> $errors */
        $errors = [];
        $onError = function (Throwable $e, array $context) use (&$errors): void {
            $errors[] = [$e, $context];
        };

        $this->assertTrue($this->relay->processBatch($onError));
        $this->assertNotEmpty($errors);
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
                $published++;
                $this->relay->requestStop();
            });

        $this->outboxRepository->expects($this->once())->method('delete');

        $this->assertTrue($this->relay->processBatch());
        $this->assertSame(1, $published);
    }
}
