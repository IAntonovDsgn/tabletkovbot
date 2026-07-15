<?php

namespace App\Application\Command\Session\TransitToState\StateHandlers;

use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Session\StateHandlerInterface;
use App\Domain\Exceptions\InvalidValueException;

final readonly class ChangeNameMedicamentEnteredStateHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository
    ) {
    }

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

        $medicament = $this->medicamentRepository->findById($currentSessionValueInt);
        if ($medicament === null) {

        }
    }

}
