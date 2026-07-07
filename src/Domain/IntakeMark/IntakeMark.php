<?php

namespace App\Domain\IntakeMark;

use DateTimeImmutable;

class IntakeMark
{
    const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private readonly string $createdAt;

    protected function __construct(
        private readonly int $userId,
        private int $medicamentId,
        private bool $isActive,
        DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private readonly ?int $id = null,
    ) {
        $this->createdAt = $createdAt->format(self::DATE_TIME_FORMAT);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function deactivate(): void
    {
        if ($this->isActive()) {
            $this->isActive = false;
        }
    }

    public function isIncludeInInterval(DateTimeImmutable $startDate, DateTimeImmutable $endDate): bool
    {
        $result = false;
        $createdAt = DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, $this->createdAt);

        if ($createdAt >= $startDate && $createdAt <= $endDate) {
            $result = true;
        }

        return $result;
    }
}
