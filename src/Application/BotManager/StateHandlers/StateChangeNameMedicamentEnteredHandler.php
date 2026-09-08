<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\DTOs\StateHandlerResponseDTO;
use App\Application\BotManager\Exceptions\InvalidValueException;
use App\Application\BotManager\Factories\KeyboardFactory;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateChangeNameMedicamentEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        if (is_null($messageText)) {
            throw new InvalidValueException(EnumMessageText::MEDICAMENT_EMPTY_NAME_ERROR->value);
        }

        $medicamentId = intval($session->getPayload());
        $medicament = $this->medicamentRepository->findById($medicamentId);

        if (is_null($medicament) || $medicament->getChatId() !== $session->getChatId()) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->setName($messageText);
        $this->medicamentRepository->update($medicament);

        return new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_RENAMED_SUCCESS,
            $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
