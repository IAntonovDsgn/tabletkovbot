<?php

namespace App\Domain\Chat;

interface ChatRepositoryInterface
{
    public function findById(int $chatId): ?Chat;

    public function save(Chat $chat): void;
}

