<?php

declare(strict_types=1);

namespace App\Domain\Entities\Medicament;

use App\Domain\Exceptions\Interior\EntityAlreadyExistInPersistenceException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;

interface MedicamentRepositoryInterface
{
    /**
     * @throws EntityAlreadyExistInPersistenceException
     * @throws RepositoryException
     */
    public function insert(Medicament $medicament): int;

    /**
     * @throws NotFoundEntityException
     * @throws RepositoryException
     */
    public function update(Medicament $medicament): void;

    public function findByChatIdAndMedicamentName(string $medicamentName, int $chatId): ?Medicament;

    public function findById(int $id): ?Medicament;

    /**
     * @return Medicament[]
     */
    public function findByChatId(int $chatId): array;
}
