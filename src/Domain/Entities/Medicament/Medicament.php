<?php

declare(strict_types=1);

namespace App\Domain\Entities\Medicament;

use DateTimeImmutable;

class Medicament
{
    const string TIME_FORMAT = 'H:i';
    const string DATE_TIME_ZONE = 'Asia/Yekaterinburg';

    private function __construct(
        private readonly ?int $id,
        private string $name,
        private readonly bool $isExistInPersistence,
        private readonly int $chatId,
        private ?DateTimeImmutable $notificationTime,
        private bool $isActive,
    ) {
    }

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
            true
        );
    }

    public static function restoreFromPersistence(
        int $id,
        string $name,
        int $chatId,
        ?DateTimeImmutable $notificationTime,
        bool $isActive,
    ):Medicament {
        return new self(
            $id,
            $name,
            true,
            $chatId,
            $notificationTime,
            $isActive,
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
