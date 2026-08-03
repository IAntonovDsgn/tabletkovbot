<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\Interior\NotFoundEntityException;

final readonly class StateDeleteMedicamentSelectedHandler implements StateHandlerInterface
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
        $medicaments = $this->medicamentRepository->findByChatId($chatId);
        $buttons = [];

        if (empty($medicaments)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        foreach ($medicaments as $medicament) {
            $buttons[] = new Button(
                $medicament->getName(),
                EnumState::CHANGE_MEDICAMENT_NAME_SELECTED,
            );
        }

        $buttons[] = new Button(Button::MENU, EnumState::MENU);

        return new StateHandlerResponseDTO(EnumMessageText::CHOOSE_MEDICAMENT, $buttons);
    }
}
