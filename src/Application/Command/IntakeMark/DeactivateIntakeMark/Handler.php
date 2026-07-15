<?php

namespace App\Application\Command\IntakeMark\DeactivateIntakeMark;

use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository
    ) {}

    /**
     * @throws DeactivateIntakeMarkException
     */
    public function __invoke(int $intakeMarkId): void
    {
         $intakeMark = $this->intakeMarkRepository->findById($intakeMarkId);

         if (is_null($intakeMark)) {
             throw new DeactivateIntakeMarkException('Intake Mark not found');
         } elseif (! $intakeMark->isActive()) {
             throw new DeactivateIntakeMarkException('Intake Mark is not active');
         }

         $intakeMark->deactivate();
         $this->intakeMarkRepository->save($intakeMark);
    }
}
