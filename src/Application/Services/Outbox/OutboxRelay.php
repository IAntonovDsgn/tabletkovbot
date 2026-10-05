<?php

declare(strict_types=1);

namespace App\Application\Services\Outbox;

use App\Application\Services\DataSender\MessageBrokerInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;
use Psr\Log\LoggerInterface;

final class OutboxRelay
{
    private bool $stopRequested = false;

    public function __construct(
        private readonly MessageOutboxRepositoryInterface $messageOutboxRepository,
        private readonly ReportOutboxRepositoryInterface $reportOutboxRepository,
        private readonly MessageBrokerInterface $broker,
        private readonly LoggerInterface $logger,
        private readonly int $batchSize,
        private readonly int $pollIntervalMs,
        private readonly int $maxAttempts = 4,
    ) {}

    public function run(): void
    {
        while ($this->stopRequested === false) {
            try {
                $this->processBatch();
                usleep($this->pollIntervalMs * 1000);
            } catch (\Exception $e) {
                $this->logger->error($e);
                usleep($this->pollIntervalMs * 1000);
            }
        }

        $this->broker->close();
    }

    /**
     * @throws AttemptsExceededException
     */
    public function processBatch(): void
    {
        $messages = $this->messageOutboxRepository->getMessages($this->batchSize);
        $reports = $this->reportOutboxRepository->getReports($this->batchSize);
        $this->publishMessages($messages);
        $this->publishReports($reports);
    }

    /**
     * @param Message[] $messages
     *
     * @throws AttemptsExceededException
     */
    private function publishMessages(array $messages): void
    {
        foreach ($messages as $message) {
            try {
                $this->broker->publishMessage($message);
            } catch (\Exception) {
                $this->registerFailedPublishMessageAttempt($message);
            }
            $this->messageOutboxRepository->delete($message);
        }
    }

    /**
     * @param Report[] $reports
     *
     * @throws AttemptsExceededException
     */
    private function publishReports(array $reports): void
    {
        foreach ($reports as $report) {
            try {
                $this->broker->publishReport($report);
            } catch (\Exception) {
                $this->registerFailedPublishReportAttempt($report);
            }
            $this->reportOutboxRepository->delete($report);
        }
    }

    /**
     * @throws AttemptsExceededException
     */
    private function registerFailedPublishMessageAttempt(Message $message): void
    {
        $attempts = $this->messageOutboxRepository->markAttempt($message);

        if ($attempts >= $this->maxAttempts) {
            $this->messageOutboxRepository->delete($message);
            throw new AttemptsExceededException('message id = '.$message->getId());
        }
    }

    /**
     * @throws AttemptsExceededException
     */
    private function registerFailedPublishReportAttempt(Report $report): void
    {
        $attempts = $this->reportOutboxRepository->markAttempt($report);

        if ($attempts >= $this->maxAttempts) {
            $this->reportOutboxRepository->delete($report);
            throw new AttemptsExceededException('report id = '.$report->getId());
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }
}
