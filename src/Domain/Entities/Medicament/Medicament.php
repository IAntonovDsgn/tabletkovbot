<?php

namespace App\Domain\Entities\Medicament;

use DateTimeImmutable;

class Medicament
{
    const string TIME_FORMAT = 'H:i';
    const string DATE_TIME_ZONE = 'Asia/Yekaterinburg';
    private ?DateTimeImmutable $notificationTime = null;

    public function __construct(
        private string $name,
        private readonly int $chatId,
        ?DateTimeImmutable $notificationTime = null,
        private bool $isActive = true,
        private readonly ?int $id = null,
    ) {
        $this->notificationTime = $notificationTime;
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