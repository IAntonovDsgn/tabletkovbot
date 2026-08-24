<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;

final readonly class StateDeleteMedicamentConfirmedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    /**
     * @throws NotFoundEntityException
     * @throws RepositoryException
     */
    public function handle(
        int $chatId,
        ?string $messageText,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $medicament = $this->medicamentRepository->findById((int)$sessionPayload);

        if (is_null($medicament) || $medicament->getChatId() !== $chatId) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->deactivate();
        $this->medicamentRepository->update($medicament);
        return new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_DELETED, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
