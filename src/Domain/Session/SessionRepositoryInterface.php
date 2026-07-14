<?php

namespace App\Domain\Session;

interface SessionRepositoryInterface
{
    public function getByChatId(int $chatId): ?Session;

    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function rollbackTransaction(): void;

    public function save(Session $session): void;
}
