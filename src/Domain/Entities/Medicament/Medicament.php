<?php

declare(strict_types=1);

namespace App\Domain\Entities\Medicament;

use App\Domain\Support\DateFormats;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;

class Medicament
{
    private function __construct(
        private readonly ?int $id,
        private string $name,
        private readonly bool $isExistInPersistence,
        private readonly int $chatId,
        private ?DateTimeImmutable $notificationTime,
        private bool $isActive,
        private ?DateTimeImmutable $lastNotificationDate,
    ) {}

    public static function create(
        string $name,
        int $chatId,
        ?DateTimeImmutable $notificationTime = null,
    ): Medicament {
        return new self(
            null,
            $name,
            false,
            $chatId,
            $notificationTime,
            true,
            null
        );
    }

    public static function restoreFromPersistence(
        int $id,
        string $name,
        int $chatId,
        ?DateTimeImmutable $notificationTime,
        bool $isActive,
        ?DateTimeImmutable $lastNotificationDate = null,
    ): Medicament {
        return new self(
            $id,
            $name,
            true,
            $chatId,
            $notificationTime,
            $isActive,
            $lastNotificationDate,
        );
    }

    public function isExistInPersistence(): bool
    {
        return $this->isExistInPersistence;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setNotificationTime(DateTimeImmutable $time): void
    {
        $this->notificationTime = $time;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markNotificationSent(): void
    {
        $this->lastNotificationDate = new DateTimeImmutable(
            'now',
            new DateTimeZone(DateFormats::TIME_ZONE)
        );
    }

    public function getLastNotificationDate(): ?DateTimeImmutable
    {
        return $this->lastNotificationDate;
    }

    public function getNotificationTime(): ?DateTimeImmutable
    {
        return $this->notificationTime;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }
}
