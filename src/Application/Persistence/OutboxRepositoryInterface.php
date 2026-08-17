<?php

declare(strict_types=1);

namespace App\Application\Persistence;

use App\Domain\Entities\Message\Message;

interface OutboxRepositoryInterface
{
    public function save(Message $message): void;

    /** @return Message[] */
    public function getPendingMessages(int $limit): array;

    public function markAsSent(int $id): void;
}
