<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateNotificationsSelectedHandler implements StateHandlerInterface
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(RequestDTO $params): void {
        $session = $this->sessionRepository->findByChatId($params->chatId);

        if (is_null($session)) {
            throw new NotFoundEntityException(EnumMessageText::INTERNAL_ERROR->value);
        }

        $isNotificationEnabled = $session->isNotificationEnabled();

        if ($isNotificationEnabled) {
            $this->outboxRepository->insert(
                Message::create(
                    $params->chatId,
                    EnumMessageText::NOTIFICATIONS_ENABLE->value,
                    [
                        new MessageButton(MessageButton::DISABLE_NOTIFICATIONS, EnumState::NOTIFICATION_DISABLED),
                        new MessageButton(MessageButton::MENU, EnumState::MENU),
                    ]
                )
            );
        } else {
            $this->outboxRepository->insert(
                Message::create(
                    $params->chatId,
                    EnumMessageText::NOTIFICATIONS_DISABLE->value,
                    [
                        new MessageButton(MessageButton::ENABLE_NOTIFICATIONS, EnumState::NOTIFICATION_ENABLED),
                        new MessageButton(MessageButton::MENU, EnumState::MENU),
                    ]
                )
            );
        }
    }
}
