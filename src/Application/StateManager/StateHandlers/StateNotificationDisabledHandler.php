<?php

declare(strict_types=1);

namespace App\Application\StateManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;

final readonly class StateNotificationDisabledHandler implements StateHandlerInterface
{
    public function __construct(
        private KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $session->disableNotifications();

        return new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED,
            $this->keyboardFactory->makeMenuKeyboard(),
        );
    }
}
