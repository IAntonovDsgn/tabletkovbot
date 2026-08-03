<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\KeyboardFactory;
use App\Domain\Entities\Session\SessionRepositoryInterface;

final readonly class StateNotificationDisabledHandler implements StateHandlerInterface
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $session = $this->sessionRepository->findByChatId($chatId);
        $session->disableNotifications();
        $this->sessionRepository->save($session);

        return new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
