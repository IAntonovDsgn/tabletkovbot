<?php

namespace App\Domain\User;

use Illuminate\Support\Facades\Date;

class User
{
    public function __construct(
        private int $id,
        private int $telegramId,
        private bool $hasNotification,
        private ?Date $createdAt = null,
        private ?Date $updatedAt = null,
    ) {
    }
}
