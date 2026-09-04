<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Repositories;

use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use App\Infrastructure\Exceptions\AlreadyExistInPersistenceException;
use App\Infrastructure\Exceptions\RepositoryException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class SessionRepository implements SessionRepositoryInterface
{
    use HydrateRowsTrait;

    const string SESSION_TABLE_NAME = 'sessions';
    const string CHAT_ID_COLUMN_NAME = 'chat_id';
    const string ID_COLUMN_NAME = 'id';
    const string IS_NOTIFICATION_ENABLED_COLUMN_NAME = 'is_notification_enabled';
    const string PAYLOAD_COLUMN_NAME = 'payload';
    const string STATE_COLUMN_NAME = 'state';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByChatId(int $chatId): ?Session
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::SESSION_TABLE_NAME)
            ->where(self::CHAT_ID_COLUMN_NAME . ' = :chatId')
            ->setParameter('chatId', $chatId);

        try {
            $row = $queryBuilder->executeQuery()->fetchAssociative();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage());
        }


        if ($row === false) {
            $result = null;
        } else {
            $result = $this->mapOrmToDomain($row);
        }

        return $result;
    }

    public function insert(Session $session): int
    {
        if ($session->isExistInPersistence()) {
            throw new AlreadyExistInPersistenceException('isExistInPersistence = true');
        }

        try {
            $this->connection->insert(
                self::SESSION_TABLE_NAME,
                [
                    self::CHAT_ID_COLUMN_NAME => $session->getChatId(),
                    self::PAYLOAD_COLUMN_NAME => $session->getPayload(),
                    self::IS_NOTIFICATION_ENABLED_COLUMN_NAME => $session->isNotificationEnabled(),
                    self::STATE_COLUMN_NAME => $session->getState()->value,
                ]
            );

            return (int)$this->connection->lastInsertId();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage());
        }
    }

    public function update(Session $session): void
    {
        if (!$session->isExistInPersistence()) {
            throw new NotFoundEntityException('isExistInPersistence = false');
        }

        try {
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
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapOrmToDomain(array $row): Session
    {
        return Session::restoreFromPersistence(
            $this->toInt($row[self::ID_COLUMN_NAME]),
            $this->toInt($row[self::CHAT_ID_COLUMN_NAME]),
            $this->toBool($row[self::IS_NOTIFICATION_ENABLED_COLUMN_NAME]),
            EnumState::tryFrom($this->toString($row[self::STATE_COLUMN_NAME])) ?? EnumState::MENU,
            $this->toStringOrNull($row[self::PAYLOAD_COLUMN_NAME]),
        );
    }
}
