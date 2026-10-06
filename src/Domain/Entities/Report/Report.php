<?php

declare(strict_types=1);

namespace App\Domain\Entities\Report;

use DateTimeImmutable;

final class Report
{
    private function __construct(
        private readonly ?int $id,
        private readonly int $chatId,
        private readonly DateTimeImmutable $startDate,
        private readonly bool $isExistInPersistence,
        private int $attempts,
    ) {}

    public static function create(
        int $chatId,
        DateTimeImmutable $startDate,
    ): Report {
        return new self(
            null,
            $chatId,
            $startDate,
            false,
            0
        );
    }

    public static function restoreFromPersistence(
        int $id,
        int $chatId,
        DateTimeImmutable $startDate,
        int $attempts,
    ): Report {
        return new self(
            $id,
            $chatId,
            $startDate,
            true,
            $attempts
        );
    }

    public function isExistInPersistence(): bool
    {
        return $this->isExistInPersistence;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }
}
