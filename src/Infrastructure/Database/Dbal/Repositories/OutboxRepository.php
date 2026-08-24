<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Repositories;

use App\Application\Persistence\OutboxRepositoryInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\Interior\EntityAlreadyExistInPersistenceException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class OutboxRepository implements OutboxRepositoryInterface
{
    use HydrateRowsTrait;

    const string MESSAGE_OUTBOX_TABLE_NAME = 'message_outbox';
    const string ID_COLUMN_NAME = 'id';
    const string CHAT_ID_COLUMN_NAME = 'chat_id';
    const string TEXT_COLUMN_NAME = 'text';
    const string BUTTONS_COLUMN_NAME = 'buttons';
    const string STATUS_COLUMN_NAME = 'status';
    const string PENDING_STATUS = 'pending';
    const string SENT_STATUS = 'sent';

    public function __construct(
        private Connection $connection
    ) {
    }

    public function insert(Message $message): void
    {
        if ($message->isExistInPersistence()) {
            throw new EntityAlreadyExistInPersistenceException('isExistInPersistence = true');
        }

        try {
            $this->connection->insert(
                self::MESSAGE_OUTBOX_TABLE_NAME,
                [
                    self::CHAT_ID_COLUMN_NAME => $message->getChatId(),
                    self::TEXT_COLUMN_NAME => $message->getText(),
                    self::BUTTONS_COLUMN_NAME =>
                        (!empty($message->getButtons()))
                            ? json_encode($message->getButtons(), JSON_UNESCAPED_UNICODE)
                            : null,
                    self::STATUS_COLUMN_NAME => self::PENDING_STATUS,
                ]
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage());
        }
    }

    public function getPendingMessages(int $limit): array
    {
        $result = [];
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MESSAGE_OUTBOX_TABLE_NAME)
            ->where(self::STATUS_COLUMN_NAME . ' = :status')
            ->setParameter('status', self::PENDING_STATUS)
            ->setMaxResults($limit);

        try {
            $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage());
        }


        if (!empty($rows)) {
            foreach ($rows as $row) {
                $result[] = $this->mapOrmToDomain($row);
            }
        }

        return $result;
    }

    public function markAsSent(Message $message): void
    {
        if (!$message->isExistInPersistence()) {
            throw new NotFoundEntityException('isExistInPersistence = false');
        }

        try {
            $this->connection->update(
                self::MESSAGE_OUTBOX_TABLE_NAME,
                [self::STATUS_COLUMN_NAME => self::SENT_STATUS],
                [self::ID_COLUMN_NAME => $message->getId()]
            );
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapOrmToDomain(array $row): Message
    {
        $buttons = [];
        $buttonsData = json_decode($this->toString($row[self::BUTTONS_COLUMN_NAME]), true);

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
