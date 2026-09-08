<?php

declare(strict_types=1);

namespace App\Application\StateManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateMakeIntakeMarkSelectedHandler implements StateHandlerInterface
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
        $medicaments = $this->medicamentRepository->findByChatId($session->getChatId());
        $buttons = [];

        if (empty($medicaments)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        foreach ($medicaments as $medicament) {
            if ($medicament->isActive()) {
                $buttons[] = new MessageButton(
                    $medicament->getName(),
                    EnumState::INTAKE_MARK_HAS_MADE,
                    (string) $medicament->getId(),
                );
            }
        }

        if (empty($buttons)) {
            $result = new StateHandlerResponseDTO(
                EnumMessageText::NOT_FOUND_ACTIVE_MEDICAMENTS,
                $this->keyboardFactory->makeMenuKeyboard()
            );
        } else {
            $buttons[] = new MessageButton(MessageButton::MENU, EnumState::MENU);
            $result = new StateHandlerResponseDTO(EnumMessageText::CHOOSE_MEDICAMENT, $buttons);
        }

        return $result;
    }
}
