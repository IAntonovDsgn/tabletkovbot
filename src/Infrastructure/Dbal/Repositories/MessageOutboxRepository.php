<?php

declare(strict_types=1);

namespace App\Infrastructure\Dbal\Repositories;

use App\Application\Services\OutboxService\MessageOutboxRepositoryInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use App\Infrastructure\Dbal\Exceptions\AlreadyExistInPersistenceException;
use App\Infrastructure\Dbal\Exceptions\NotExistInPersistenceException;
use App\Infrastructure\Dbal\Exceptions\RepositoryException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class MessageOutboxRepository implements MessageOutboxRepositoryInterface
{
    use HydrateRowsTrait;

    public const string MESSAGE_OUTBOX_TABLE_NAME = 'message_outbox';
    public const string ID_COLUMN_NAME = 'id';
    public const string CHAT_ID_COLUMN_NAME = 'chat_id';
    public const string TEXT_COLUMN_NAME = 'text';
    public const string BUTTONS_COLUMN_NAME = 'buttons';
    public const string ATTEMPTS_COLUMN_NAME = 'attempts';
    public const string TYPE_COLUMN_NAME = 'type';

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
                ]
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws NotExistInPersistenceException
     * @throws RepositoryException
     */
    public function update(Message $message): void
    {
        if (! $message->isExistInPersistence()) {
            throw new NotExistInPersistenceException('notExistInPersistence = false');
        }

        $data = [
            self::CHAT_ID_COLUMN_NAME => $message->getChatId(),
            self::TEXT_COLUMN_NAME => $message->getText(),
            self::ATTEMPTS_COLUMN_NAME => $message->getAttempts(),
            self::BUTTONS_COLUMN_NAME
            => (!empty($message->getButtons()))
                ? json_encode($message->getButtons(), JSON_UNESCAPED_UNICODE)
                : null,
        ];

        try {
            $this->connection->update(
                self::MESSAGE_OUTBOX_TABLE_NAME,
                $data,
                [self::ID_COLUMN_NAME => $message->getId()]
            );
        } catch (\Exception $e) {
            throw new RepositoryException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return Message[]
     *
     * @throws RepositoryException
     */
    public function getMessages(int $limit): array
    {
        $result = [];
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MESSAGE_OUTBOX_TABLE_NAME)
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
     * @throws NotFoundEntityException
     * @throws RepositoryException
     * @throws NotExistInPersistenceException
     */
    public function markAttempt(Message $message): int
    {
        if (!$message->isExistInPersistence()) {
            throw new NotFoundEntityException('isExistInPersistence = false');
        }

        $attempts = $message->getAttempts();
        $message->setAttempts($attempts + 1);
        $this->update($message);

        return $message->getAttempts();
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

    private function mapOrmToDomain(array $row): Message
    {
        $buttons = [];
        $buttonsJson = $this->toStringOrNull($row[self::BUTTONS_COLUMN_NAME]);
        $buttonsData = $buttonsJson !== null ? json_decode($buttonsJson, true) : [];

        if (is_array($buttonsData)) {
            foreach ($buttonsData as $buttonData) {
                if (! is_array($buttonData)) {
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
            $this->toInt($row[self::ATTEMPTS_COLUMN_NAME]),
            $this->toStringOrNull($row[self::TEXT_COLUMN_NAME]),
            $buttons,
        );
    }
}
