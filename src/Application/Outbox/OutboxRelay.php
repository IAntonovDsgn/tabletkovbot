<?php

declare(strict_types=1);

namespace App\Application\Outbox;

use App\Application\Message\MessageBrokerInterface;
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
            }
        }

        return $relayed;
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }
}
