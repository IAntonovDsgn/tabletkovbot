<?php

declare(strict_types=1);

namespace App\Domain\Entities\IntakeMark;

use App\Domain\Exceptions\Interior\EntityAlreadyExistInPersistenceException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use Doctrine\DBAL\Exception;

interface IntakeMarkRepositoryInterface
{
    /**
     * @throws EntityAlreadyExistInPersistenceException
     * @throws Exception
     */
    public function insert(IntakeMark $intakeMark): void;

    /**
     * @throws NotFoundEntityException
     * @throws Exception
     */
    public function update(IntakeMark $intakeMark): void;

    public function findById(int $id): ?IntakeMark;

    /**
     * @return IntakeMark[]
     */
    public function findByChatId(int $chatId): array;
}
