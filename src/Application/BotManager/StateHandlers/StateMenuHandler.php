<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;

final readonly class StateMenuHandler implements StateHandlerInterface
{
    public function __construct(
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        return new StateHandlerResponseDTO(
            EnumMessageText::MENU, $this->keyboardFactory->makeMenuKeyboard(),
        );
    }
}
