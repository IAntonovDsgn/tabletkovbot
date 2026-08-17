<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;

final readonly class StateNotificationEnabledHandler implements StateHandlerInterface
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
        $session = $this->sessionRepository->findByChatId($chatId) ?? new Session($chatId);
        $session->enableNotifications();
        $this->sessionRepository->save($session);

        return new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}