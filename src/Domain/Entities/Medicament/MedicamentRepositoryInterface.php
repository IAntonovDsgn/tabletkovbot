<?php

declare(strict_types=1);

namespace App\Domain\Entities\Medicament;

interface MedicamentRepositoryInterface
{
    public function insert(Medicament $medicament): int;

    public function update(Medicament $medicament): void;

    public function findByChatIdAndMedicamentName(string $medicamentName, int $chatId): ?Medicament;

    public function findById(int $id): ?Medicament;

    /**
     * @return Medicament[]
     */
    public function findByChatId(int $chatId): array;

    /**
     * @return Medicament[]
     */
    public function findForNotificationNow(): array;
}
