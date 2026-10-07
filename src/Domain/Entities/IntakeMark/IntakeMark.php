<?php

declare(strict_types=1);

namespace App\Domain\Entities\IntakeMark;

use DateTimeImmutable;

final readonly class IntakeMark
{
    private function __construct(
        private ?int $id,
        private bool $isExistInPersistence,
        private int $chatId,
        private int $medicamentId,
        private DateTimeImmutable $createdAt,
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
        );
    }

    public static function restoreFromPersistence(
        int $id,
        int $chatId,
        int $medicamentId,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            $id,
            true,
            $chatId,
            $medicamentId,
            $createdAt,
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
