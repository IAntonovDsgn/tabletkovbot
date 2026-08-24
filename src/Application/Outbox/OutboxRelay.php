<?php

declare(strict_types=1);

namespace App\Application\Outbox;

use App\Application\Message\MessageBrokerInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Exceptions\Interior\RepositoryException;
use Psr\Log\LoggerInterface;

final class OutboxRelay
{
    private bool $stopRequested = false;

    public function __construct(
        private readonly OutboxRepositoryInterface $outboxRepository,
        private readonly MessageBrokerInterface $broker,
        private readonly LoggerInterface $logger,
        private readonly int $batchSize,
        private readonly int $pollIntervalMs,
        private readonly int $maxAttempts = 4,
    ) {
    }

    public function run(): void
    {
        $this->logger->info('Outbox relay started', [
            'batch_size' => $this->batchSize,
            'poll_interval_ms' => $this->pollIntervalMs,
        ]);

        while (!$this->stopRequested) {
            try {
                if (!$this->processBatch()) {
                    usleep($this->pollIntervalMs * 1000);
                }
            } catch (\Exception $e) {
                $this->logger->error('Outbox relay cycle failed, will retry', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                usleep($this->pollIntervalMs * 1000);
            }
        }

        $this->broker->close();
        $this->logger->info('Outbox relay stopped gracefully');
    }

    /**
     * @throws RepositoryException
     */
    public function processBatch(): bool
    {
        $messages = $this->outboxRepository->getPendingMessages($this->batchSize);

        $relayed = false;
        foreach ($messages as $message) {
            if ($this->stopRequested) {
                break;
            }

            try {
                $this->broker->publish($message);
                $this->outboxRepository->delete($message);
                $relayed = true;
            } catch (\Exception $e) {
                $this->logger->error('Failed to relay outbox message', [
                    'message_id' => $message->getId(),
                    'chat_id' => $message->getChatId(),
                    'error' => $e->getMessage(),
                ]);
                $this->registerFailedAttempt($message, $e);
            }
        }

        return $relayed;
    }

    private function registerFailedAttempt(Message $message, \Exception $error): void
    {
        try {
            $attempts = $this->outboxRepository->markAttempt($message);
        } catch (\Exception $e) {
            // Counter update failed: keep the row pending, it will be retried.
            $this->logger->warning('Failed to register outbox delivery attempt', [
                'message_id' => $message->getId(),
                'chat_id' => $message->getChatId(),
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($attempts < $this->maxAttempts) {
            return;
        }

        $this->logger->error('Dropping outbox message after repeated failures', [
            'message_id' => $message->getId(),
            'chat_id' => $message->getChatId(),
            'attempts' => $attempts,
            'last_error' => $error->getMessage(),
        ]);

        try {
            $this->outboxRepository->delete($message);
        } catch (\Exception $e) {
            // Same best-effort policy as delete-after-publish.
            $this->logger->warning('Failed to delete dropped outbox message', [
                'message_id' => $message->getId(),
                'chat_id' => $message->getChatId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }
}
