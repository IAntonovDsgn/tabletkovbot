<?php

declare(strict_types=1);

namespace App\Application\Outbox;

use App\Domain\Entities\Message\Message;

interface OutboxRepositoryInterface
{
    public function insert(Message $message): void;

    /**
     * @return Message[]
     */
    public function getPendingMessages(int $limit): array;

    /**
     * Registers one failed delivery attempt for the message.
     *
     * @return int The updated attempts counter value.
     */
    public function markAttempt(Message $message): int;

    public function delete(Message $message): void;
}
