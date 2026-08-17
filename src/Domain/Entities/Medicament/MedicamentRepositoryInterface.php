<?php

declare(strict_types=1);

namespace App\Domain\Entities\Medicament;

interface MedicamentRepositoryInterface
{
    public function save(Medicament $medicament): int;

    public function findByChatIdAndMedicamentName(string $medicamentName, int $chatId): ?Medicament;

    public function findById(int $id): ?Medicament;

    /**
     * @return Medicament[]
     */
    public function findByChatId(int $chatId): array;
}
