<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Repositories;

use RuntimeException;

trait HydratesRows
{
    protected function toInt(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        throw new RuntimeException(sprintf('Expected numeric row value, got "%s"', get_debug_type($value)));
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        throw new RuntimeException(sprintf('Expected boolean row value, got "%s"', get_debug_type($value)));
    }

    protected function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        throw new RuntimeException(sprintf('Expected string row value, got "%s"', get_debug_type($value)));
    }

    protected function toStringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        throw new RuntimeException(sprintf('Expected string|null row value, got "%s"', get_debug_type($value)));
    }
}
