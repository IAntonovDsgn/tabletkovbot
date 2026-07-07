<?php

namespace App\Application\Query\Medicament\GetMedicamentsByUserId;

use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {
    }

    /**
     * @return Medicament[]
     */
    public function __invoke(int $userId): array
    {
        $medicaments = $this->medicamentRepository->findByUserId($userId);

        foreach ($medicaments as $key => $medicament) {
            if (! $medicament->isActive()) {
                unset($medicaments[$key]);
            }
        }

        return $medicaments;
    }
}
