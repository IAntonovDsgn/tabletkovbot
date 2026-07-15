<?php

namespace App\Application\Command\Chat\EnableNotifications;

use App\Domain\Entities\Chat\ChatRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
    ) {
    }

    /**
     * @throws EnableNotificationsException
     */
    public function __invoke(int $chatId): void
    {
        $chat = $this->chatRepository->findById($chatId);

        if (is_null($chat)) {
            throw new EnableNotificationsException('Чат не найден');
        }

        $chat->enableNotifications();
        $this->chatRepository->save($chat);
    }
}
