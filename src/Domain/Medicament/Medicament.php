<?php

namespace App\Domain\Medicament;

use DateTimeImmutable;

final class Medicament
{
    public function __construct (
        private string $name,
        private readonly int $userId,
        private ?DateTimeImmutable $notificationTime = null,
        private bool $isActive = true,
        private readonly ?int $id = null,
        private readonly ?DateTimeImmutable $createdAt = null,
        private readonly ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @throws MedicamentException
     */
    public function setName(string $name): void
    {
        if ($name === '') {
            throw new MedicamentException('Name cannot be empty');
        }

        $this->name = $name;
    }

    public function setNotificationTime(DateTimeImmutable $time): void
    {
        $time->format('H:i:s');
        $this->notificationTime = $time;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
