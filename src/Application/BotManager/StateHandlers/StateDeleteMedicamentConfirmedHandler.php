<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\KeyboardFactory;
use App\Domain\Exceptions\Interior\NotFoundEntityException;

final readonly class StateDeleteMedicamentConfirmedHandler implements StateHandlerInterface
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
        $medicament = $this->medicamentRepository->findById($sessionPayload);

        if (is_null($medicament)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->deactivate();
        $this->medicamentRepository->save($medicament);
        return new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_DELETED, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
