<?php

namespace App\Application\Command\ChangeMedicamentName;

use App\Domain\Medicament\MedicamentException;
use App\Domain\Medicament\MedicamentRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    )
    {}

    /**
     * @throws ChangeMedicamentNameException
     */
    public function __invoke(int $medicamentId, string $newName): void
    {
        $medicament = $this->medicamentRepository->findById($medicamentId);

        if (is_null($medicament)) {
            throw new ChangeMedicamentNameException('Медикамент не найден');
        } elseif ($medicament->isActive() === false) {
            throw new ChangeMedicamentNameException('Медикамент был удален');
        }

        try {
            $medicament->setName($newName);
        } catch (MedicamentException $exception) {
            throw new ChangeMedicamentNameException($exception->getMessage());
        }

        $this->medicamentRepository->save($medicament);
    }
}
