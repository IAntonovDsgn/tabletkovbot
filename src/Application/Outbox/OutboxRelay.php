<?php

declare(strict_types=1);

namespace App\Application\Outbox;

use App\Application\MessageService\MessageBrokerInterface;
use App\Domain\Entities\Message\Message;
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
    ) {}

    public function run(): void
    {
        while (!$this->stopRequested) {
            try {
                if (!$this->processBatch()) {
                    usleep($this->pollIntervalMs * 1000);
                }
            } catch (\Exception $e) {
                $this->logger->error($e, ['phase' => 'cycle']);
                usleep($this->pollIntervalMs * 1000);
            }
        }

        $this->broker->close();
    }

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
                $this->logger->error($e, [
                    'message_id' => $message->getId(),
                    'chat_id' => $message->getChatId(),
                    'phase' => 'relay',
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
            $this->logger->error($e, [
                'message_id' => $message->getId(),
                'chat_id' => $message->getChatId(),
                'phase' => 'register_attempt',
            ]);

            return;
        }

        if ($attempts < $this->maxAttempts) {
            return;
        }

        $this->logger->error($error, [
            'message_id' => $message->getId(),
            'chat_id' => $message->getChatId(),
            'attempts' => $attempts,
            'phase' => 'drop_after_max_attempts',
        ]);

        try {
            $this->outboxRepository->delete($message);
        } catch (\Exception $e) {
            $this->logger->error($e, [
                'message_id' => $message->getId(),
                'chat_id' => $message->getChatId(),
                'phase' => 'delete_dropped',
            ]);
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }
}
