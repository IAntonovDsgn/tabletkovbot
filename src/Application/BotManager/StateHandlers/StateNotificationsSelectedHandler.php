<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateNotificationsSelectedHandler implements StateHandlerInterface
{

    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {
    }

    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $session = $this->sessionRepository->findByChatId($chatId);

        if ($session->isNotificationEnabled()) {
            $result = new StateHandlerResponseDTO(
                EnumMessageText::NOTIFICATIONS_ENABLE,
                [
                    new Button(Button::DISABLE_NOTIFICATIONS, EnumState::NOTIFICATION_DISABLED)
                ]
            );
        } else {
            $result = new StateHandlerResponseDTO(
                EnumMessageText::NOTIFICATIONS_DISABLE,
                [
                    new Button(Button::ENABLE_NOTIFICATIONS, EnumState::NOTIFICATION_ENABLED)
                ]
            );
        }

        return $result;
    }
}
