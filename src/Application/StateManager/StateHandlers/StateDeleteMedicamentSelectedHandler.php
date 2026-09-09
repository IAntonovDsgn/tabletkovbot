<?php

declare(strict_types=1);

namespace App\Application\StateManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateDeleteMedicamentSelectedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $medicaments = $this->medicamentRepository->findByChatId($session->getChatId());
        $buttons = [];

        if (empty($medicaments)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        foreach ($medicaments as $medicament) {
            if (! $medicament->isActive()) {
                continue;
            }
            $buttons[] = new MessageButton(
                $medicament->getName(),
                EnumState::SELECTED_MEDICAMENT_FOR_DELETE,
                (string) $medicament->getId(),
            );
        }

        $buttons[] = new MessageButton(MessageButton::MENU, EnumState::MENU);

        return new StateHandlerResponseDTO(EnumMessageText::CHOOSE_MEDICAMENT, $buttons);
    }
}
