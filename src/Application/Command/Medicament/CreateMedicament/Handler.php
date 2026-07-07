<?php

namespace App\Application\Command\Medicament\CreateMedicament;

use App\Domain\Medicament\MedicamentFactory;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use DateTimeImmutable;

final readonly class Handler
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    )
    {}

    /**
     * @throws CreateMedicamentException
     */
    public function __invoke(int $userId, string $medicamentName, ?DateTimeImmutable $notificationTime): void
    {
        $sameExistMedicament = $this->medicamentRepository->findByUserIdAndMedicamentName($medicamentName, $userId);

        if (! is_null($sameExistMedicament)) {
            throw new CreateMedicamentException('Медикамент уже существует');
        }

        $medicament = MedicamentFactory::create($medicamentName, $userId, $notificationTime);
        $this->medicamentRepository->save($medicament);
    }
}
