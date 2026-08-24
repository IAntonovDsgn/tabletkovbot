<?php

declare(strict_types=1);

namespace App\Domain\Entities\Session;

use App\Domain\Exceptions\Interior\EntityAlreadyExistInPersistenceException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;

interface SessionRepositoryInterface
{
    /**
     * @throws RepositoryException
     */
    public function findByChatId(int $chatId): ?Session;

    /**
     * @throws EntityAlreadyExistInPersistenceException
     * @throws RepositoryException
     */
    public function insert(Session $session): int;

    /**
     * @throws NotFoundEntityException
     * @throws RepositoryException
     */
    public function update(Session $session): void;
}
