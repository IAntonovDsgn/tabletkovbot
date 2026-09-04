<?php

declare(strict_types=1);

namespace Tests\Support;

use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

class FakeLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
    public array $records = [];

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => $this->extractMessage($message),
            'context' => $context,
        ];
    }

    public function hasMessage(string $needle): bool
    {
        return array_any($this->records, fn($record) => str_contains($record['message'], $needle));
    }

    public function countMessages(string $needle): int
    {
        $count = 0;
        foreach ($this->records as $record) {
            if (str_contains($record['message'], $needle)) {
                $count++;
            }
        }

        return $count;
    }

    public function hasRecordWithContext(string $key, mixed $value): bool
    {
        return array_any(
            $this->records,
            fn($record) => isset($record['context'][$key]) && $record['context'][$key] === $value
        );
    }

    private function extractMessage(string|Stringable $message): string
    {
        if ($message instanceof Throwable) {
            return $message->getMessage();
        }

        return (string) $message;
    }
}
