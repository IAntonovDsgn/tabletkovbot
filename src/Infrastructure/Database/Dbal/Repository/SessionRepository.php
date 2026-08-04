<?php

namespace App\Infrastructure\Database\Dbal\Repository;

use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use Doctrine\DBAL\Connection;

class SessionRepository implements SessionRepositoryInterface
{
    const string SESSION_TABLE_NAME = 'sessions';
    const string CHAT_ID_COLUMN_NAME = 'chat_id';
    const string IS_NOTIFICATION_ENABLED_COLUMN_NAME = 'is_notification_enabled';
    const string PAYLOAD_COLUMN_NAME = 'payload';
    const string STATE_COLUMN_NAME = 'state';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByChatId(int $chatId): ?Session
    {
        // TODO: Implement findByChatId() method.
    }

    public function save(Session $session): void
    {
        // TODO: Implement save() method.
    }
}
