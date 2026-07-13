<?php

namespace App\Application\Command\IntakeMark\CreateIntakeMark;

use App\Domain\Chat\ChatRepositoryInterface;
use App\Domain\IntakeMark\IntakeMark;
use App\Domain\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Medicament\MedicamentRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
        private MedicamentRepositoryInterface $medicamentRepository,
        private ChatRepositoryInterface $chatRepository
    ) {
    }

    /**
     * @throws CreateIntakeMarkException
     */
    public function __invoke(int $medicamentId, int $chatId): void
    {
        $chat = $this->chatRepository->findById($chatId);
        $medicament = $this->medicamentRepository->findById($medicamentId);

        if (is_null($medicament)) {
            throw new CreateIntakeMarkException('Медикамент не найден');
        } elseif (! $medicament->isActive()) {
            throw new CreateIntakeMarkException('Медикамент не активен');
        } elseif (is_null($chat)) {
            throw new CreateIntakeMarkException('Чат не найден');
        }

        $intakeMark = new IntakeMark($chatId, $medicamentId);
        $this->intakeMarkRepository->save($intakeMark);
    }
}
