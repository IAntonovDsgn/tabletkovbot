<?php

namespace App\Application\Command\Session\DisableNotifications;

use App\Domain\Entities\Session\SessionRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {
    }

    /**
     * @throws DisableNotificationsException
     */
    public function __invoke(int $chatId): void
    {
        $session = $this->sessionRepository->findByChatId($chatId);

        if (is_null($session)) {
            throw new DisableNotificationsException('Чат не найден');
        }

        $session->disableNotifications();
        $this->sessionRepository->save($session);
    }
}
