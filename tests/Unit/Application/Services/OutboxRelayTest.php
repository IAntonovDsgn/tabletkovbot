<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Services;

use App\Application\Services\DataSender\MessageBrokerInterface;
use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\Services\Outbox\OutboxRelay;
use App\Application\Services\Outbox\ReportOutboxRepositoryInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OutboxRelayTest extends TestCase
{
    private const int CHAT_ID = 12345;
    private const int BATCH_SIZE = 10;
    private const int MAX_ATTEMPTS = 3;

    private MockObject $messageRepository;
    private MockObject $reportRepository;
    private MockObject $broker;
    private MockObject $logger;
    private OutboxRelay $relay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $this->reportRepository = $this->createMock(ReportOutboxRepositoryInterface::class);
        $this->broker = $this->createMock(MessageBrokerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->relay = new OutboxRelay(
            $this->messageRepository,
            $this->reportRepository,
            $this->broker,
            $this->logger,
            self::BATCH_SIZE,
            1000,
            self::MAX_ATTEMPTS,
        );
    }

    /**
     * @param Message[] $messages
     */
    private function withMessages(array $messages): void
    {
        $this->messageRepository->expects($this->once())
            ->method('getMessages')
            ->with(self::BATCH_SIZE)
            ->willReturn($messages);
    }

    /**
     * @param Report[] $reports
     */
    private function withReports(array $reports): void
    {
        $this->reportRepository->expects($this->once())
            ->method('getReports')
            ->with(self::BATCH_SIZE)
            ->willReturn($reports);
    }

    private function makeMessage(int $id): Message
    {
        return Message::restoreFromPersistence($id, self::CHAT_ID, 0, 'текст');
    }

    private function makeReport(): Report
    {
        return Report::restoreFromPersistence(
            7,
            self::CHAT_ID,
            new DateTimeImmutable('2023-01-01'),
            0,
        );
    }

    public function testDeletesMessageOnlyAfterTheBrokerConfirms(): void
    {
        $message = $this->makeMessage(1);
        $this->withMessages([$message]);

        $calls = [];
        $this->broker->method('publishMessage')
            ->willReturnCallback(function () use (&$calls): void {
                $calls[] = 'publish';
            });
        $this->messageRepository->method('delete')
            ->willReturnCallback(function () use (&$calls): void {
                $calls[] = 'delete';
            });

        $this->relay->processBatch();

        self::assertSame(['publish', 'delete'], $calls);
    }

    public function testDeletesReportOnlyAfterTheBrokerConfirms(): void
    {
        $report = $this->makeReport();
        $this->withReports([$report]);

        $calls = [];
        $this->broker->method('publishReport')
            ->willReturnCallback(function () use (&$calls): void {
                $calls[] = 'publish';
            });
        $this->reportRepository->method('delete')
            ->willReturnCallback(function () use (&$calls): void {
                $calls[] = 'delete';
            });

        $this->relay->processBatch();

        self::assertSame(['publish', 'delete'], $calls);
    }

    public function testKeepsRowAndCountsAttemptWhenPublishFails(): void
    {
        $message = $this->makeMessage(1);
        $this->withMessages([$message]);

        $this->broker->method('publishMessage')->willThrowException(new RuntimeException('broker down'));
        $this->messageRepository->expects($this->once())
            ->method('markAttempt')
            ->with($message)
            ->willReturn(1);
        $this->messageRepository->expects($this->never())->method('delete');
        $this->logger->expects($this->never())->method('error');

        $this->relay->processBatch();
    }

    public function testDropsMessageAfterMaxAttempts(): void
    {
        $message = $this->makeMessage(1);
        $this->withMessages([$message]);

        $this->broker->method('publishMessage')->willThrowException(new RuntimeException('broker down'));
        $this->messageRepository->method('markAttempt')->willReturn(self::MAX_ATTEMPTS);
        $this->messageRepository->expects($this->once())->method('delete')->with($message);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('attempts exhausted'),
                $this->callback(
                    static fn(array $context): bool
                        => $context['phase'] === 'outbox_publish'
                        && $context['kind'] === 'message'
                        && $context['attempts'] === self::MAX_ATTEMPTS,
                ),
            );

        $this->relay->processBatch();
    }

    public function testDropsReportAfterMaxAttempts(): void
    {
        $report = $this->makeReport();
        $this->withReports([$report]);

        $this->broker->method('publishReport')->willThrowException(new RuntimeException('broker down'));
        $this->reportRepository->method('markAttempt')->willReturn(self::MAX_ATTEMPTS);
        $this->reportRepository->expects($this->once())->method('delete')->with($report);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('attempts exhausted'),
                $this->callback(
                    static fn(array $context): bool
                        => $context['phase'] === 'outbox_publish'
                        && $context['kind'] === 'report',
                ),
            );

        $this->relay->processBatch();
    }

    public function testOneFailedMessageDoesNotBlockTheRestOfTheBatch(): void
    {
        $broken = $this->makeMessage(1);
        $good = $this->makeMessage(2);
        $this->withMessages([$broken, $good]);

        $this->broker->method('publishMessage')
            ->willReturnCallback(static function (Message $message) use ($broken): void {
                if ($message->getId() === $broken->getId()) {
                    throw new RuntimeException('poison message');
                }
            });
        $this->messageRepository->method('markAttempt')->willReturn(1);
        $this->messageRepository->expects($this->once())
            ->method('delete')
            ->with($good);

        $this->relay->processBatch();
    }

    public function testMessageFailureDoesNotStopReportPublishing(): void
    {
        $message = $this->makeMessage(1);
        $report = $this->makeReport();
        $this->withMessages([$message]);
        $this->withReports([$report]);

        $this->broker->method('publishMessage')->willThrowException(new RuntimeException('broker down'));
        $this->messageRepository->method('markAttempt')->willReturn(1);
        $this->reportRepository->expects($this->once())->method('delete')->with($report);

        $this->relay->processBatch();
    }

    public function testRequestsTheBatchSizeFromBothRepositories(): void
    {
        $this->messageRepository->expects($this->once())
            ->method('getMessages')
            ->with(self::BATCH_SIZE)
            ->willReturn([]);
        $this->reportRepository->expects($this->once())
            ->method('getReports')
            ->with(self::BATCH_SIZE)
            ->willReturn([]);

        $this->relay->processBatch();
    }

    public function testRunClosesTheBrokerAfterAStopRequest(): void
    {
        // requestStop() before run() means the polling loop body never executes at all.
        $this->messageRepository->expects($this->never())->method('getMessages');
        $this->reportRepository->expects($this->never())->method('getReports');

        $this->broker->expects($this->once())->method('close');
        $this->broker->expects($this->never())->method('publishMessage');

        $this->relay->requestStop();
        $this->relay->run();
    }
}
