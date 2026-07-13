<?php

namespace App\Domain\Chat;

interface ChatRepositoryInterface
{
    public function findById(int $chatId): ?Chat;

    public function save(Chat $chat): void;

    public function startTransaction(): void;

    public function finishTransaction(): void;
}

