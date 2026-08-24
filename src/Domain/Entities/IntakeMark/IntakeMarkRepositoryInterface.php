<?php

declare(strict_types=1);

namespace App\Domain\Entities\IntakeMark;

use App\Domain\Exceptions\Interior\EntityAlreadyExistInPersistenceException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;

interface IntakeMarkRepositoryInterface
{
    /**
     * @throws EntityAlreadyExistInPersistenceException
     * @throws RepositoryException
     */
    public function insert(IntakeMark $intakeMark): int;

    /**
     * @throws NotFoundEntityException
     * @throws RepositoryException
     */
    public function update(IntakeMark $intakeMark): void;

    public function findById(int $id): ?IntakeMark;

    /**
     * @return IntakeMark[]
     */
    public function findByChatId(int $chatId): array;
}
