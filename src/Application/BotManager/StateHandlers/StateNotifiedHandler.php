<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\KeyboardFactory;
use App\Domain\Exceptions\Interior\SendMessageException;

final readonly class StateNotifiedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    /**
     * @throws SendMessageException
     */
    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $medicament = $this->medicamentRepository->findById($text);

        if (is_null($medicament)) {
            throw new SendMessageException('Medicament not found');
        }

        $intakeMark = new IntakeMark(
            $chatId,
            $medicament->getId(),
        );

        $this->intakeMarkRepository->save($intakeMark);

        return new StateHandlerResponseDTO(
            EnumMessageText::INTAKE_MARK_SAVED, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
