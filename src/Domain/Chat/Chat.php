<?php

namespace App\Domain\Chat;

class Chat
{
    protected function __construct(
        private readonly int $chatId,
        private bool $hasNotification,
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
