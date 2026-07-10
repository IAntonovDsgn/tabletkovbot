<?php

namespace App\Application\Query\Medicament\GetMedicamentsByChatId;

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
    public function __invoke(int $chatId): array
    {
        $medicaments = $this->medicamentRepository->findByChatId($chatId);

        foreach ($medicaments as $key => $medicament) {
            if (! $medicament->isActive()) {
                unset($medicaments[$key]);
            }
        }

        return $medicaments;
    }
}
