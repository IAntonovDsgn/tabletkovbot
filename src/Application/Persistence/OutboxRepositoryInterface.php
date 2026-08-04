<?php

namespace App\Application\Persistence;

use App\Domain\Entities\Message\Message;

interface OutboxRepositoryInterface
{
    public function save(Message $message): void;

    /** @return Message[] */
    public function getPending(int $limit): array;

    public function markAsSent(int $id): void;
}
