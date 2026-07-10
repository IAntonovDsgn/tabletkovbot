<?php

namespace App\Domain\Chat;

class ChatFactory extends Chat
{
    public static function create(
        int $chatId,
        bool $hasNotification = true,
    ): Chat
    {
        return new Chat($chatId, $hasNotification);
    }
}
