<?php

declare(strict_types=1);

namespace App\Application\Services\Outbox;

use App\Application\Services\DataSender\MessageBrokerInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;
use Exception;
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
            } catch (Exception $e) {
                $this->logger->error($e);
                usleep($this->pollIntervalMs * 1000);
            }
        }

        $this->broker->close();
    }

    public function processBatch(): void
    {
        $messages = $this->messageOutboxRepository->getMessages($this->batchSize);
        $reports = $this->reportOutboxRepository->getReports($this->batchSize);
        $this->publishMessages($messages);
        $this->publishReports($reports);
    }

    /**
     * @param Message[] $messages
     */
    private function publishMessages(array $messages): void
    {
        foreach ($messages as $message) {
            try {
                $this->broker->publishMessage($message);
            } catch (Exception) {
                $this->registerFailedPublishMessageAttempt($message);

                continue;
            }

            $this->messageOutboxRepository->delete($message);
        }
    }

    /**
     * @param Report[] $reports
     */
    private function publishReports(array $reports): void
    {
        foreach ($reports as $report) {
            try {
                $this->broker->publishReport($report);
            } catch (Exception) {
                $this->registerFailedPublishReportAttempt($report);

                continue;
            }

            $this->reportOutboxRepository->delete($report);
        }
    }

    private function registerFailedPublishMessageAttempt(Message $message): void
    {
        $attempts = $this->messageOutboxRepository->markAttempt($message);

        if ($attempts < $this->maxAttempts) {
            return;
        }

        $this->messageOutboxRepository->delete($message);
        $this->logger->error(
            sprintf('Publish attempts exhausted, message dropped: id = %s', $message->getId()),
            ['phase' => 'outbox_publish', 'kind' => 'message', 'attempts' => $attempts],
        );
    }

    private function registerFailedPublishReportAttempt(Report $report): void
    {
        $attempts = $this->reportOutboxRepository->markAttempt($report);

        if ($attempts < $this->maxAttempts) {
            return;
        }

        $this->reportOutboxRepository->delete($report);
        $this->logger->error(
            sprintf('Publish attempts exhausted, report dropped: id = %s', $report->getId()),
            ['phase' => 'outbox_publish', 'kind' => 'report', 'attempts' => $attempts],
        );
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }
}
