<?php

declare(strict_types=1);

namespace App\Domain\Entities\Medicament;

use App\Domain\Exceptions\NotFoundEntityException;
use App\Infrastructure\Dbal\Exceptions\AlreadyExistInPersistenceException;
use App\Infrastructure\Dbal\Exceptions\RepositoryException;
use DateMalformedStringException;

interface MedicamentRepositoryInterface
{
    /**
     * @throws RepositoryException
     * @throws AlreadyExistInPersistenceException
     */
    public function insert(Medicament $medicament): int;

    /**
     * @throws RepositoryException
     * @throws NotFoundEntityException
     * @throws AlreadyExistInPersistenceException
     */
    public function update(Medicament $medicament): void;

    /**
     * @throws RepositoryException
     */
    public function findByChatIdAndMedicamentName(string $medicamentName, int $chatId): ?Medicament;

    /**
     * @throws RepositoryException
     */
    public function findById(int $id): ?Medicament;

    /**
     * @return Medicament[]
     * @throws RepositoryException
     *
     * @return Medicament[]
     */
    public function findByChatId(int $chatId): array;

    /**
     * @throws RepositoryException
     * @throws DateMalformedStringException
     *
     * @return Medicament[]
     */
    public function findForNotificationNow(): array;
}
