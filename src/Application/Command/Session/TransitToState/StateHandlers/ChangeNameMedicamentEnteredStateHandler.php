<?php

namespace App\Application\Command\Session\TransitToState\StateHandlers;

use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Session\StateHandlerInterface;
use App\Domain\Exceptions\InvalidValueException;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class ChangeNameMedicamentEnteredStateHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository
    ) {
    }

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function handle(string $currentSessionValue, string $newSessionValue = ''): void
    {
        if ($newSessionValue === '') {
            throw new InvalidValueException('New medicament name cannot be empty');
        } else {
            $newMedicamentName = $newSessionValue;
        }

        $currentSessionValueInt = intval($currentSessionValue);

        if ($currentSessionValueInt === 0) {
            throw new InvalidValueException("Current session value is not int: \n" . $currentSessionValue);
        }

        $this->medicamentRepository->beginTransaction();
        try {
            $medicament = $this->medicamentRepository->findById($currentSessionValueInt);
            if ($medicament === null) {
                throw new NotFoundEntityException('Medicament with id ' . $currentSessionValueInt . ' not found');
            }
            $medicament->setName($newMedicamentName);
            $this->medicamentRepository->save($medicament);
            $this->medicamentRepository->commitTransaction();
        } catch (\Exception $e) {
            $this->medicamentRepository->rollbackTransaction();
            throw $e;
        }
    }

}
