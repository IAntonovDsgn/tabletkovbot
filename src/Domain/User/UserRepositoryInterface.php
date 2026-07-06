<?php

namespace App\Domain\User;

interface UserRepositoryInterface
{
    public function findByTelegramId(int $telegramId): ?User;
}

