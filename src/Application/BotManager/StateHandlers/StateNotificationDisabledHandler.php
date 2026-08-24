<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;

final readonly class StateNotificationDisabledHandler implements StateHandlerInterface
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
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
        $session = $this->sessionRepository->findByChatId($chatId);

        if (is_null($session)) {
            throw new NotFoundEntityException("Session not found with chatId = $chatId");
        }

        $session->disableNotifications();
        $this->sessionRepository->update($session);

        return new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
