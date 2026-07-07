<?php

namespace App\Application\Command\Medicament\ChangeMedicamentNotificationTime;

use App\Domain\Medicament\MedicamentRepositoryInterface;
use DateTimeImmutable;
use ValueError;

final readonly class Handler
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository
    ) {
    }

    /**
     * @throws ChangeMedicamentNotificationTimeException
     */
    public function __invoke(int $medicamentId, string $newTime): void
    {
        try {
            $time = DateTimeImmutable::createFromFormat('H:i:s', $newTime);
        } catch (ValueError $exception) {
            throw new ChangeMedicamentNotificationTimeException($exception->getMessage());
        }

        $medicament = $this->medicamentRepository->findById($medicamentId);

        if (is_null($medicament)) {
            throw new ChangeMedicamentNotificationTimeException('Не найден медикамен');
        } elseif (! $medicament->isActive()) {
            throw new ChangeMedicamentNotificationTimeException('Медикамент был удален');
        }

        $medicament->setNotificationTime($time);
        $this->medicamentRepository->save($medicament);
    }
}
