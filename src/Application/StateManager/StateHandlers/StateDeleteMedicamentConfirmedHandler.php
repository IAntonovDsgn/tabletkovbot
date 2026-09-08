<?php

declare(strict_types=1);

namespace App\Application\StateManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateDeleteMedicamentConfirmedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $medicament = $this->medicamentRepository->findById((int) $session->getPayload());

        if (is_null($medicament) || $medicament->getChatId() !== $session->getChatId()) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->deactivate();
        $this->medicamentRepository->update($medicament);
        return new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_DELETED,
            $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
