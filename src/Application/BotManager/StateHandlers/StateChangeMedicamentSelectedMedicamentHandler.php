<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\MessageButton;
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
            if ((string) $medicament->getId() === $buttonPayload) {
                $medicamentId = $medicament->getId();
                break;
            }
        }

        if (is_null($medicamentId)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        return new StateHandlerResponseDTO(
            EnumMessageText::WHAT_YOU_WANT_TO_CHANGE,
            [
                new MessageButton(MessageButton::CHANGE_NAME, EnumState::CHANGE_MEDICAMENT_NAME_SELECTED),
                new MessageButton(MessageButton::CHANGE_NOTIFICATION_TIME, EnumState::CHANGE_NOTIFICATION_TIME_SELECTED),
                new MessageButton(MessageButton::MENU, EnumState::MENU)
            ],
            (string) $medicamentId
        );
    }
}