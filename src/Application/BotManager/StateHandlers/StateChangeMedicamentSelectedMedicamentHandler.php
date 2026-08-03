<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\Interior\NotFoundEntityException;

final readonly class StateChangeMedicamentSelectedMedicamentHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
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
        $medicamentId = null;
        $medicaments = $this->medicamentRepository->findByChatId($chatId);
        foreach ($medicaments as $medicament) {
            if ($medicament->getId() === $buttonPayload) {
                $medicamentId = $medicament->getId();
            }
        }

        if (is_null($medicamentId)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        return new StateHandlerResponseDTO(
            EnumMessageText::WHAT_YOU_WANT_TO_CHANGE,
            [
                new Button(Button::CHANGE_NAME, EnumState::CHANGE_MEDICAMENT_NAME_SELECTED),
                new Button(Button::CHANGE_NOTIFICATION_TIME, EnumState::CHANGE_NOTIFICATION_TIME_SELECTED),
                new Button(Button::MENU, EnumState::MENU)
            ],
            $medicamentId
        );
    }
}
