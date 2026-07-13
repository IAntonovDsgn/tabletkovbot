<?php

namespace App\Application\Command\Medicament\CreateMedicament;

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
    public function __invoke(int $chatId, string $medicamentName, ?DateTimeImmutable $notificationTime): void
    {
        $sameExistMedicament = $this->medicamentRepository->findByChatIdAndMedicamentName($medicamentName, $chatId);

        if (! is_null($sameExistMedicament)) {
            throw new CreateMedicamentException('Медикамент уже существует');
        }

        $medicament = new Medicament(
            $medicamentName,
            $chatId,
            $notificationTime,
        );

        $this->medicamentRepository->save($medicament);
    }
}
