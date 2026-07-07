<?php

namespace App\Application\Command\IntakeMark\CreateIntakeMark;

use App\Domain\IntakeMark\IntakeMarkFactory;
use App\Domain\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
        private MedicamentRepositoryInterface $medicamentRepository,
        private UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * @throws CreateIntakeMarkException
     */
    public function __invoke(int $medicamentId, int $userId): void
    {
        $user = $this->userRepository->findByUserId($userId);
        $medicament = $this->medicamentRepository->findById($medicamentId);

        if (is_null($medicament)) {
            throw new CreateIntakeMarkException('Не найден медикамент');
        } elseif (! $medicament->isActive()) {
            throw new CreateIntakeMarkException('Медикамент не активен');
        } elseif (is_null($user)) {
            throw new CreateIntakeMarkException('Не найден пользователь');
        }

        $intakeMark = IntakeMarkFactory::create($userId, $medicamentId);
        $this->intakeMarkRepository->save($intakeMark);
    }
}
