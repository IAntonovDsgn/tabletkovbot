<?php

namespace App\Domain\Medicament;

interface MedicamentRepositoryInterface
{
    public function save(Medicament $medicament): void;

    public function findByUserIdAndMedicamentName(string $medicamentName, int $userId): ?Medicament;

    public function findById(int $id): ?Medicament;

    /**
     * @return Medicament[]
     */
    public function findByUserId(int $userId): array;
}
