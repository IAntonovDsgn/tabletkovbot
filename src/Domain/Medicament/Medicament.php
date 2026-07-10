<?php

namespace App\Domain\Medicament;

use DateTimeImmutable;

class Medicament
{
    const string TIME_FORMAT = 'H:i:s';
    private readonly string $notificationTime;

    protected function __construct(
        private readonly string $name,
        private readonly int $chatId,
        private bool $isActive,
        ?DateTimeImmutable $notificationTime = null,
        private readonly ?int $id = null,
    ) {
        if (! is_null($notificationTime)) {
            $this->notificationTime = $notificationTime->format(self::TIME_FORMAT);
        }
    }

    public function setNotificationTime(DateTimeImmutable $time): void
    {
        $this->notificationTime = $time->format(self::TIME_FORMAT);
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
