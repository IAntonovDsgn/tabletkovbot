<?php

declare(strict_types=1);

namespace App\Application\Services\Outbox;

use App\Domain\Entities\Message\Message;

interface MessageOutboxRepositoryInterface
{
    public function insert(Message $message): void;

    /**
     * @return Message[]
     */
    public function getMessages(int $limit): array;

    public function markAttempt(Message $message): int;

    public function delete(Message $message): void;
}
