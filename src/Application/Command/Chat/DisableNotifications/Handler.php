<?php

namespace App\Application\Command\Chat\DisableNotifications;

use App\Domain\Entities\Chat\ChatRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
    ) {
    }

    /**
     * @throws DisableNotificationsException
     */
    public function __invoke(int $chatId): void
    {
        $chat = $this->chatRepository->findById($chatId);

        if (is_null($chat)) {
            throw new DisableNotificationsException('Чат не найден');
        }

        $chat->disableNotifications();
        $this->chatRepository->save($chat);
    }
}
