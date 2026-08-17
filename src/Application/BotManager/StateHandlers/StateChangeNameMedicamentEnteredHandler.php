<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Exceptions\External\InvalidValueException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;

final readonly class StateChangeNameMedicamentEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        if (is_null($text)) {
            throw new InvalidValueException(EnumMessageText::MEDICAMENT_EMPTY_NAME_ERROR->value);
        }

        $medicamentId = intval($sessionPayload);
        $medicament = $this->medicamentRepository->findById($medicamentId);

        if (is_null($medicament) || $medicament->getChatId() !== $chatId) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->setName($text);
        $this->medicamentRepository->save($medicament);

        return new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_RENAMED_SUCCESS, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
