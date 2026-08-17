<?php

namespace App\Domain\Entities\IntakeMark;

use DateTimeImmutable;

class IntakeMark
{
    const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly int $chatId,
        private readonly int $medicamentId,
        DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private bool $isActive = true,
        private readonly ?int $id = null,
    ) {
        $this->createdAt = $createdAt;
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
        return $this->createdAt >= $startDate && $this->createdAt <= $endDate;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getMedicamentId(): int
    {
        return $this->medicamentId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
