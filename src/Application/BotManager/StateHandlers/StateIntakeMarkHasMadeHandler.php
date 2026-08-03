<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\KeyboardFactory;
use App\Domain\Exceptions\Interior\NotFoundEntityException;

final readonly class StateIntakeMarkHasMadeHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    /**
     * @throws NotFoundEntityException
     */
    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $intakeMark = null;
        $medicaments = $this->medicamentRepository->findByChatId($chatId);
        if (empty($medicaments)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        foreach ($medicaments as $medicament) {
            if ($medicament->getName() === $buttonPayload) {
                $intakeMark = new IntakeMark(
                    $chatId,
                    $medicament->getId()
                );
            }
        }

        if (is_null($intakeMark)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        return new StateHandlerResponseDTO(
            EnumMessageText::INTAKE_MARK_SAVED, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
