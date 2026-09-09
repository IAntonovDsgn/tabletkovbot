<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Repositories;

use App\Application\Outbox\OutboxRepositoryInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use App\Infrastructure\Exceptions\AlreadyExistInPersistenceException;
use App\Infrastructure\Exceptions\RepositoryException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class OutboxRepository implements OutboxRepositoryInterface
{
    use HydrateRowsTrait;

    public const string MESSAGE_OUTBOX_TABLE_NAME = 'message_outbox';
    public const string ID_COLUMN_NAME = 'id';
    public const string CHAT_ID_COLUMN_NAME = 'chat_id';
    public const string TEXT_COLUMN_NAME = 'text';
    public const string BUTTONS_COLUMN_NAME = 'buttons';
    public const string STATUS_COLUMN_NAME = 'status';
    public const string ATTEMPTS_COLUMN_NAME = 'attempts';
    public const string PENDING_STATUS = 'pending';

    public function __construct(
        private Connection $connection
    ) {}

    /**
     * @throws RepositoryException
     * @throws AlreadyExistInPersistenceException
     */
    public function insert(Message $message): void
    {
        if ($message->isExistInPersistence()) {
            throw new AlreadyExistInPersistenceException('isExistInPersistence = true');
        }

        try {
            $this->connection->insert(
                self::MESSAGE_OUTBOX_TABLE_NAME,
                [
                    self::CHAT_ID_COLUMN_NAME => $message->getChatId(),
                    self::TEXT_COLUMN_NAME => $message->getText(),
                    self::BUTTONS_COLUMN_NAME
                        => (!empty($message->getButtons()))
                            ? json_encode($message->getButtons(), JSON_UNESCAPED_UNICODE)
                            : null,
                    self::STATUS_COLUMN_NAME => self::PENDING_STATUS,
                ]
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function getPendingMessages(int $limit): array
    {
        $result = [];
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MESSAGE_OUTBOX_TABLE_NAME)
            ->where(self::STATUS_COLUMN_NAME . ' = :status')
            ->setParameter('status', self::PENDING_STATUS)
            ->orderBy(self::ID_COLUMN_NAME, 'ASC')
            ->setMaxResults($limit);

        try {
            $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }


        if (!empty($rows)) {
            foreach ($rows as $row) {
                $result[] = $this->mapOrmToDomain($row);
            }
        }

        return $result;
    }

    /**
     * Registers one failed delivery attempt for the message.
     *
     * @return int The updated attempts counter value.
     *
     * @throws NotFoundEntityException
     * @throws RepositoryException
     */
    public function markAttempt(Message $message): int
    {
        if (!$message->isExistInPersistence()) {
            throw new NotFoundEntityException('isExistInPersistence = false');
        }

        try {
            $this->connection->executeStatement(
                sprintf(
                    'UPDATE %s SET %s = %s + 1 WHERE %s = :id',
                    self::MESSAGE_OUTBOX_TABLE_NAME,
                    self::ATTEMPTS_COLUMN_NAME,
                    self::ATTEMPTS_COLUMN_NAME,
                    self::ID_COLUMN_NAME,
                ),
                ['id' => $message->getId()],
            );

            $attempts = $this->connection->fetchOne(
                sprintf(
                    'SELECT %s FROM %s WHERE %s = :id',
                    self::ATTEMPTS_COLUMN_NAME,
                    self::MESSAGE_OUTBOX_TABLE_NAME,
                    self::ID_COLUMN_NAME,
                ),
                ['id' => $message->getId()],
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }

        if ($attempts === false) {
            throw new NotFoundEntityException('Outbox row disappeared');
        }

        return $this->toInt($attempts);
    }

    /**
     * @throws RepositoryException
     * @throws NotFoundEntityException
     */
    public function delete(Message $message): void
    {
        if (!$message->isExistInPersistence()) {
            throw new NotFoundEntityException('isExistInPersistence = false');
        }

        try {
            $this->connection->delete(
                self::MESSAGE_OUTBOX_TABLE_NAME,
                [self::ID_COLUMN_NAME => $message->getId()]
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapOrmToDomain(array $row): Message
    {
        $buttons = [];
        $buttonsJson = $this->toStringOrNull($row[self::BUTTONS_COLUMN_NAME]);
        $buttonsData = $buttonsJson !== null ? json_decode($buttonsJson, true) : [];

        if (is_array($buttonsData)) {
            foreach ($buttonsData as $buttonData) {
                if (!is_array($buttonData)) {
                    continue;
                }

                $state = isset($buttonData['new_state']) && is_string($buttonData['new_state'])
                    ? EnumState::tryFrom($buttonData['new_state'])
                    : null;

                if ($state !== null && isset($buttonData['title']) && is_string($buttonData['title'])) {
                    $buttons[] = new MessageButton(
                        $buttonData['title'],
                        $state,
                        isset($buttonData['additional_payload']) && is_string($buttonData['additional_payload'])
                            ? $buttonData['additional_payload']
                            : null,
                    );
                }
            }
        }

        return Message::restoreFromPersistence(
            $this->toInt($row[self::ID_COLUMN_NAME]),
            $this->toInt($row[self::CHAT_ID_COLUMN_NAME]),
            $this->toStringOrNull($row[self::TEXT_COLUMN_NAME]),
            $buttons,
        );
    }
}
