<?php

namespace App\Application\Command\Session\EnableNotifications;

use App\Domain\Entities\Session\SessionRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {
    }

    /**
     * @throws EnableNotificationsException
     */
    public function __invoke(int $chatId): void
    {
        $chat = $this->sessionRepository->findByChatId($chatId);

        if (is_null($chat)) {
            throw new EnableNotificationsException('Чат не найден');
        }

        $chat->enableNotifications();
        $this->sessionRepository->save($chat);
    }
}
