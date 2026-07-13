<?php

namespace App\Domain\Chat;

class Chat
{
    public function __construct(
        private readonly int $id,
        private bool $hasNotification = true,
    ) {
    }

    public function enableNotifications(): void
    {
        $this->hasNotification = true;
    }

    public function disableNotifications(): void
    {
        $this->hasNotification = false;
    }
}
