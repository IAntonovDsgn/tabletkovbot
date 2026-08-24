<?php

declare(strict_types=1);

namespace App\Domain\Entities\IntakeMark;

use DateTimeImmutable;

class IntakeMark
{
    const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    private function __construct(
        private readonly ?int $id,
        private readonly bool $isExistInPersistence,
        private readonly int $chatId,
        private readonly int $medicamentId,
        private readonly DateTimeImmutable $createdAt,
        private bool $isActive,
    ) {}

    public static function create(
        int $chatId,
        int $medicamentId
    ): self {
        return new self(
            null,
            false,
            $chatId,
            $medicamentId,
            new DateTimeImmutable(),
            true
        );
    }

    public static function restoreFromPersistence(
        int $id,
        int $chatId,
        int $medicamentId,
        DateTimeImmutable $createdAt,
        bool $isActive
    ): self {
        return new self(
            $id,
            true,
            $chatId,
            $medicamentId,
            $createdAt,
            $isActive
        );
    }

    public function isExistInPersistence(): bool
    {
        return $this->isExistInPersistence;
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
