<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\DTOs\StateHandlerResponseDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateNotificationsSelectedHandler implements StateHandlerInterface
{
    public function __construct() {}

    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $isNotificationEnabled = $session->isNotificationEnabled();

        if ($isNotificationEnabled) {
            $result = new StateHandlerResponseDTO(
                EnumMessageText::NOTIFICATIONS_ENABLE,
                [
                    new MessageButton(MessageButton::DISABLE_NOTIFICATIONS, EnumState::NOTIFICATION_DISABLED),
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            );
        } else {
            $result = new StateHandlerResponseDTO(
                EnumMessageText::NOTIFICATIONS_DISABLE,
                [
                    new MessageButton(MessageButton::ENABLE_NOTIFICATIONS, EnumState::NOTIFICATION_ENABLED),
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            );
        }

        return $result;
    }
}
