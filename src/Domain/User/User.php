<?php

namespace App\Domain\User;

class User
{
    protected function __construct(
        private readonly int $telegramId,
        private bool $hasNotification,
        private readonly ?int $id = null
    ) {
    }
}
