<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\KeyboardFactory;
use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use Exception;

final readonly class StateNotifiedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    /**
     * @throws NotFoundEntityException
     * @throws Exception
     */
    public function handle(
        int $chatId,
        ?string $messageText,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        if ($messageText === null) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }
        $medicament = $this->medicamentRepository->findById((int) $messageText);

        if (is_null($medicament) || $medicament->getChatId() !== $chatId) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $this->intakeMarkRepository->insert(
            IntakeMark::create($chatId, (int) $medicament->getId())
        );

        return new StateHandlerResponseDTO(
            EnumMessageText::INTAKE_MARK_SAVED, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
