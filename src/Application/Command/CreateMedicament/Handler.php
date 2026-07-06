<?php

namespace App\Application\Command\CreateMedicament;

use App\Domain\Medicament\Medicament;
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
    public function __invoke(int $userId, string $medicamentName, ?string $notificationTime): void
    {
        $sameExistMedicament = $this->medicamentRepository->findByUserIdAndMedicamentName($medicamentName, $userId);

        if (! is_null($sameExistMedicament)) {
            throw new CreateMedicamentException('Медикамент уже существует');
        }

        if (! is_null($notificationTime)) {
            $notificationTimeFormated = DateTimeImmutable::createFromFormat('H:i:s', $notificationTime);
        }

        $medicament = new Medicament(
            $medicamentName,
            $userId,
            $notificationTimeFormated ?? null,
        );

        $this->medicamentRepository->save($medicament);
    }
}
