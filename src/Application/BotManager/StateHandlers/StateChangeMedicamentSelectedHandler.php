<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\KeyboardFactory;
use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateChangeMedicamentSelectedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(
        int $chatId,
        ?string $messageText,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $medicaments = $this->medicamentRepository->findByChatId($chatId);
        $buttons = [];

        if (empty($medicaments)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        foreach ($medicaments as $medicament) {
            if (!$medicament->isActive()) {
                continue;
            }
            $buttons[] = new MessageButton(
                $medicament->getName(),
                EnumState::SELECTED_MEDICAMENT_FOR_CHANGE,
                (string) $medicament->getId(),
            );
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
