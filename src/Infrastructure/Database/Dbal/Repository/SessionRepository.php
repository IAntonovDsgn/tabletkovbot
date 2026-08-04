<?php

namespace App\Infrastructure\Database\Dbal\Repository;

use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;

class SessionRepository implements SessionRepositoryInterface
{
    public function findByChatId(int $chatId): ?Session
    {
        // TODO: Implement findByChatId() method.
    }

    public function save(Session $session): void
    {
        // TODO: Implement save() method.
    }
}
