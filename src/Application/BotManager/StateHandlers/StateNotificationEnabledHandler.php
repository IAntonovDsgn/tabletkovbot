<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\KeyboardFactory;
use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;

final readonly class StateNotificationEnabledHandler implements StateHandlerInterface
{
    public function __construct(
        private KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $session->enableNotifications();

        return new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED,
            $this->keyboardFactory->makeMenuKeyboard(),
        );
    }
}
