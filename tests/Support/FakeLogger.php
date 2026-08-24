<?php

declare(strict_types=1);

namespace Tests\Support;

use Psr\Log\AbstractLogger;
use Stringable;

class FakeLogger extends AbstractLogger
{
    public array $records = [];

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string)$message,
            'context' => $context,
        ];
    }

    public function hasMessage(string $message): bool
    {
        foreach ($this->records as $record) {
            if ($record['message'] === $message) {
                return true;
            }
        }

        return false;
    }

    public function countMessages(string $message): int
    {
        $count = 0;
        foreach ($this->records as $record) {
            if ($record['message'] === $message) {
                $count++;
            }
        }

        return $count;
    }
}
