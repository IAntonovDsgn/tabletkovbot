<?php

namespace App\Domain\User;

class UserFactory extends User
{
    public static function create(
        int $telegramId,
        bool $hasNotification = true,
    ): User
    {
        return new User($telegramId, $hasNotification);
    }
}
