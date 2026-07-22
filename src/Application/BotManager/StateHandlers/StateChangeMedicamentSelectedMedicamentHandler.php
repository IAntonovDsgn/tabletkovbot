<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\HandlerOutputDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumOutgoingText;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\NotSendToClient\NotFoundEntityException;

final readonly class StateChangeMedicamentSelectedMedicamentHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {
    }

    /**
     * @throws NotFoundEntityException
     */
    public function handle(int $chatId, ?string $text, ?string $payload, ?string $clickedButtonTitle): HandlerOutputDTO
    {
        $medicamentId = null;
        $medicaments = $this->medicamentRepository->findByChatId($chatId);
        foreach ($medicaments as $medicament) {
            if ($medicament->getName() === $clickedButtonTitle) {
                $medicamentId = $medicament->getId();
            }
        }

        if (is_null($medicamentId)) {
            throw new NotFoundEntityException(EnumOutgoingText::MEDICAMENT_NOT_FOUND->value);
        }

        return new HandlerOutputDTO(
            EnumOutgoingText::WHAT_YOU_WANT_TO_CHANGE,
            [
                new Button(Button::CHANGE_NAME, EnumState::CHANGE_MEDICAMENT_NAME_SELECTED),
                new Button(Button::CHANGE_NOTIFICATION_TIME, EnumState::CHANGE_NOTIFICATION_TIME_SELECTED)
            ],
            $medicamentId
        );
    }
}
