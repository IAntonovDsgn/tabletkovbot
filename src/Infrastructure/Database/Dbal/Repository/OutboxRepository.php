<?php

namespace App\Infrastructure\Database\Dbal\Repository;

use App\Application\Persistence\OutboxRepositoryInterface;
use App\Domain\Entities\Message\Message;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PDO;

final readonly class OutboxRepository implements OutboxRepositoryInterface
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @throws Exception
     */
    public function save(Message $message): void
    {
        $this->connection->insert('messages', [
            'chat_id' => $message->getChatId(),
            'text' => $message->getText(),
            'buttons' => !empty($message->getButtons()) ? json_encode(
                $message->getButtons(),
                JSON_UNESCAPED_UNICODE
            ) : null,
            'status' => 'pending',
        ]);
    }

    /**
     * @return Message[]
     * @throws Exception
     */
    public function getPending(int $limit): array
    {
        // Используем FOR UPDATE SKIP LOCKED для блокировки строк на уровне БД.
        // Пока воркер обрабатывает эти строки, другой воркер их просто пропустит.
        $sql = "SELECT id, chat_id, text, buttons 
                FROM outbox_messages 
                WHERE status = 'pending' 
                LIMIT :limit 
                FOR UPDATE SKIP LOCKED";

        // Выполняем запрос с приведением типа limit к INT (важно для PDO)
        $rows = $this->connection->executeQuery(
            $sql,
            ['limit' => $limit],
            ['limit' => PDO::PARAM_INT]
        )->fetchAllAssociative();

        $messages = [];

        foreach ($rows as $row) {
            $messages[] = new Message(
                (int)$row['chat_id'],
                $row['text'],
                $row['buttons'] ? json_decode($row['buttons'], true) : null,
                (int)$row['id']
            );
        }

        return $messages;
    }

    /**
     * @throws Exception
     */
    public function markAsSent(int $id): void
    {
        $this->connection->update(
            'messages',
            ['status' => 'sent'],
            ['id' => $id]
        );
    }
}
