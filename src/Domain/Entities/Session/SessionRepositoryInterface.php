<?php

namespace App\Domain\Entities\Session;

interface SessionRepositoryInterface
{
    public function findByChatId(int $chatId): ?Session;

    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function rollbackTransaction(): void;

    public function save(Session $session): void;
}
