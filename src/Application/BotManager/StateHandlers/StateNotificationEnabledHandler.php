<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\KeyboardFactory;
use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateNotificationEnabledHandler implements StateHandlerInterface
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
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
        $session = $this->sessionRepository->findByChatId($chatId);

        if (is_null($session)) {
            throw new NotFoundEntityException("Session not found with chatId = $chatId");
        }

        $session->enableNotifications();
        $this->sessionRepository->update($session);

        return new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED,
            $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
