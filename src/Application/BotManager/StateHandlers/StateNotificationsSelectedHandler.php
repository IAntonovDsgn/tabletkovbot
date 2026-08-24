<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\Interior\RepositoryException;

final readonly class StateNotificationsSelectedHandler implements StateHandlerInterface
{

    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {
    }

    /**
     * @throws RepositoryException
     */
    public function handle(
        int $chatId,
        ?string $messageText,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $session = $this->sessionRepository->findByChatId($chatId);
        $isNotificationEnabled = $session?->isNotificationEnabled() ?? true;

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
