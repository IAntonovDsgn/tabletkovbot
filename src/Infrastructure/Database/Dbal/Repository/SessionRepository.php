<?php

namespace App\Infrastructure\Database\Dbal\Repository;

use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class SessionRepository implements SessionRepositoryInterface
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

    /**
     * @throws Exception
     */
    public function findByChatId(int $chatId): ?Session
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::SESSION_TABLE_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME . ' = :chatId')
            ->setParameter('chatId', $chatId);

        $row  = $queryBuilder->executeQuery()->fetchAssociative();

        if ($row === false) {
            $result = null;
        } else {
            $result = $this->hydrate($row);
        }

        return $result;
    }


    /**
     * @throws Exception
     */
    public function save(Session $session): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $existingSession = $queryBuilder->select('*')
            ->from(self::SESSION_TABLE_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME . ' = :chatId')
            ->setParameter('chatId', $session->getChatId())
            ->executeQuery()->fetchAssociative();

        if ($existingSession === false) {
            $this->connection->insert(
                self::SESSION_TABLE_NAME,
                [
                    self::CHAT_ID_COLUMN_NAME => $session->getChatId(),
                    self::PAYLOAD_COLUMN_NAME => $session->getPayload(),
                    self::IS_NOTIFICATION_ENABLED_COLUMN_NAME => $session->isNotificationEnabled(),
                    self::STATE_COLUMN_NAME => $session->getState()->value,
                ]
            );
        } else {
            $this->connection->update(
                self::SESSION_TABLE_NAME,
                [
                    self::PAYLOAD_COLUMN_NAME => $session->getPayload(),
                    self::IS_NOTIFICATION_ENABLED_COLUMN_NAME => $session->isNotificationEnabled(),
                    self::STATE_COLUMN_NAME => $session->getState()->value,
                ],
                [
                    self::CHAT_ID_COLUMN_NAME => $session->getChatId(),
                ]
            );
        }


    }

    private function hydrate(array $row): Session
    {
        return new Session(
            (int) $row[self::CHAT_ID_COLUMN_NAME],
            (bool) $row[self::IS_NOTIFICATION_ENABLED_COLUMN_NAME],
            $row[self::PAYLOAD_COLUMN_NAME],
            EnumState::tryFrom($row[self::STATE_COLUMN_NAME]),
        );
    }
}
