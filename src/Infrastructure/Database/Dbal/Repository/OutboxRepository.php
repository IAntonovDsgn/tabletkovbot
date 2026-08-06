<?php

namespace App\Infrastructure\Database\Dbal\Repository;

use App\Application\Persistence\OutboxRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class OutboxRepository implements OutboxRepositoryInterface
{
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

    /**
     * @throws Exception
     */
    public function save(Message $message): void
    {
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
    }

    /**
     * @return Message[]
     * @throws Exception
     */
    public function getPendingMessages(int $limit): array
    {
        $result = [];
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('*')
            ->from(self::MESSAGE_OUTBOX_TABLE_NAME)
            ->where(self::STATUS_COLUMN_NAME . ' = :status')
            ->setParameter('status', self::PENDING_STATUS);

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $result[] = $this->hydrate($row);
            }
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function markAsSent(int $id): void
    {
        $this->connection->update(
            self::MESSAGE_OUTBOX_TABLE_NAME,
            [self::STATUS_COLUMN_NAME => self::SENT_STATUS],
            [self::ID_COLUMN_NAME => $id]
        );
    }

    private function hydrate(array $row): Message
    {
        return new Message(
            (int) $row[self::CHAT_ID_COLUMN_NAME],
            EnumMessageText::tryFrom($row[self::TEXT_COLUMN_NAME]),
            json_decode($row[self::BUTTONS_COLUMN_NAME], true),
            (int) $row[self::ID_COLUMN_NAME],
        );
    }
}
