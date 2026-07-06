<?php

namespace App\Application\Command\DeactivateMedicament;

use App\Domain\Medicament\MedicamentRepositoryInterface;

final readonly class Handler
{

    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {
    }

    /**
     * @throws DeactivateMedicamentException
     */
    public function __invoke(int $medicamentId): void
    {
        $medicament = $this->medicamentRepository->findById($medicamentId);
        if (is_null($medicament)) {
            throw new DeactivateMedicamentException('Медикамент не найден');
        } elseif ($medicament->isActive() === false) {
            throw new DeactivateMedicamentException('Медикамент был удален');
        }

        $medicament->deactivate();
        $this->medicamentRepository->save($medicament);
    }
}
