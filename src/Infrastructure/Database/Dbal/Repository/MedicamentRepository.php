<?php

namespace App\Infrastructure\Database\Dbal\Repository;

use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;

class MedicamentRepository implements MedicamentRepositoryInterface
{
    public function save(Medicament $medicament): void
    {
        // TODO: Implement save() method.
    }

    public function findByChatIdAndMedicamentName(string $medicamentName, int $chatId): ?Medicament
    {
        // TODO: Implement findByChatIdAndMedicamentName() method.
    }

    public function findById(int $id): ?Medicament
    {
        // TODO: Implement findById() method.
    }

    public function findByChatId(int $chatId): array
    {
        // TODO: Implement findByChatId() method.
    }
}
