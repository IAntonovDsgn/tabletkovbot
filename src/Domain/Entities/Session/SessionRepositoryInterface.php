<?php

namespace App\Domain\Entities\Session;

interface SessionRepositoryInterface
{
    public function findByChatId(int $chatId): ?Session;

    public function save(Session $session): void;
}
