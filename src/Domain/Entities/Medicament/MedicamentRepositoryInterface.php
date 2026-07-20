<?php

namespace App\Domain\Entities\Medicament;

interface MedicamentRepositoryInterface
{
    public function save(Medicament $medicament): void;

    public function findByChatIdAndMedicamentName(string $medicamentName, int $chatId): ?Medicament;

    public function findById(int $id): ?Medicament;

    /**
     * @return Medicament[]
     */
    public function findByChatId(int $chatId): array;
}
