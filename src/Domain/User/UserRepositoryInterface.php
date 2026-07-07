<?php

namespace App\Domain\User;

interface UserRepositoryInterface
{
    public function findByTelegramId(int $telegramId): ?User;

    public function findByUserId(int $userId): ?User;

    public function save(User $user): void;
}

