<?php

declare(strict_types=1);

namespace App\Application\Outbox;

use App\Application\Message\Services\MessageBrokerInterface;
use App\Domain\Entities\Message\Message;
use Closure;
use Throwable;

final class OutboxRelay
{
    private bool $stopRequested = false;

    public function __construct(
        private readonly OutboxRepositoryInterface $outboxRepository,
        private readonly MessageBrokerInterface $broker,
        private readonly int $batchSize,
        private readonly int $pollIntervalMs,
        private readonly int $maxAttempts = 4,
    ) {}

    /**
     * @param null|Closure(Throwable, array<string, mixed>): void $onError
     */
    public function run(?Closure $onError = null): void
    {
        while (!$this->stopRequested) {
            try {
                if (!$this->processBatch($onError)) {
                    usleep($this->pollIntervalMs * 1000);
                }
            } catch (Throwable $e) {
                $this->report($e, ['phase' => 'cycle'], $onError);
                usleep($this->pollIntervalMs * 1000);
            }
        }

        $this->broker->close();
    }

    public function processBatch(?Closure $onError = null): bool
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
            } catch (Throwable $e) {
                $this->report($e, [
                    'message_id' => $message->getId(),
                    'chat_id' => $message->getChatId(),
                    'phase' => 'relay',
                ], $onError);
                $this->registerFailedAttempt($message, $e, $onError);
            }
        }

        return $relayed;
    }

    private function registerFailedAttempt(Message $message, Throwable $error, ?Closure $onError = null): void
    {
        try {
            $attempts = $this->outboxRepository->markAttempt($message);
        } catch (Throwable $e) {
            $this->report($e, [
                'message_id' => $message->getId(),
                'chat_id' => $message->getChatId(),
                'phase' => 'register_attempt',
            ], $onError);

            return;
        }

        if ($attempts < $this->maxAttempts) {
            return;
        }

        $this->report($error, [
            'message_id' => $message->getId(),
            'chat_id' => $message->getChatId(),
            'attempts' => $attempts,
            'phase' => 'drop_after_max_attempts',
        ], $onError);

        try {
            $this->outboxRepository->delete($message);
        } catch (Throwable $e) {
            $this->report($e, [
                'message_id' => $message->getId(),
                'chat_id' => $message->getChatId(),
                'phase' => 'delete_dropped',
            ], $onError);
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }

    /**
     * @param array<string, string|int|null> $context
     */
    private function report(Throwable $e, array $context, ?Closure $onError = null): void
    {
        if ($onError !== null) {
            $onError($e, $context);
        }
    }
}
