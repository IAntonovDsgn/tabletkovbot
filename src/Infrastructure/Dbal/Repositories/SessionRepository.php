<?php

declare(strict_types=1);

namespace App\Infrastructure\Dbal\Repositories;

use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use App\Infrastructure\Dbal\Exceptions\AlreadyExistInPersistenceException;
use App\Infrastructure\Dbal\Exceptions\RepositoryException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class SessionRepository implements SessionRepositoryInterface
{
    use HydrateRowsTrait;

    public const string SESSION_TABLE_NAME = 'sessions';
    public const string CHAT_ID_COLUMN_NAME = 'chat_id';
    public const string ID_COLUMN_NAME = 'id';
    public const string IS_NOTIFICATION_ENABLED_COLUMN_NAME = 'is_notification_enabled';
    public const string PAYLOAD_COLUMN_NAME = 'payload';
    public const string STATE_COLUMN_NAME = 'state';

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws RepositoryException
     */
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
            throw new RepositoryException($e->getMessage(), 0, $e);
        }


        if ($row === false) {
            $result = null;
        } else {
            $result = $this->mapOrmToDomain($row);
        }

        return $result;
    }

    /**
     * @throws RepositoryException
     * @throws AlreadyExistInPersistenceException
     */
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
                    self::IS_NOTIFICATION_ENABLED_COLUMN_NAME => $session->isNotificationEnabled() ? 1 : 0,
                    self::STATE_COLUMN_NAME => $session->getState()->value,
                ]
            );

            return (int) $this->connection->lastInsertId();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws RepositoryException
     * @throws NotFoundEntityException
     */
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
                    self::IS_NOTIFICATION_ENABLED_COLUMN_NAME => $session->isNotificationEnabled() ? 1 : 0,
                    self::STATE_COLUMN_NAME => $session->getState()->value,
                ],
                [
                    self::CHAT_ID_COLUMN_NAME => $session->getChatId(),
                ]
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
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
