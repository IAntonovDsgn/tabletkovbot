<?php

declare(strict_types=1);

namespace App\Application\Persistence;

use App\Domain\Entities\Message\Message;
use App\Domain\Exceptions\Interior\EntityAlreadyExistInPersistenceException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;

interface OutboxRepositoryInterface
{
    /**
     * @throws EntityAlreadyExistInPersistenceException
     * @throws RepositoryException
     */
    public function insert(Message $message): void;

    /**
     * @return Message[]
     * @throws RepositoryException
     */
    public function getPendingMessages(int $limit): array;

    /**
     * @throws NotFoundEntityException
     * @throws RepositoryException
     */
    public function markAsSent(Message $message): void;
}
