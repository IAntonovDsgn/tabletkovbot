<?php

namespace App\Application\Command\Chat\CreateChat;

use App\Domain\Chat\Chat;
use App\Domain\Chat\ChatRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
    ) {
    }

    /**
     * @throws CreateChatException
     */
    public function __invoke(int $chatId): void
    {
        $this->chatRepository->startTransaction();
        $chat = $this->chatRepository->findById($chatId);

        if (is_null($chat)) {
            $newChat = new Chat($chatId);
            $this->chatRepository->save($newChat);
            $this->chatRepository->finishTransaction();
        } else {
            $this->chatRepository->finishTransaction();
            throw new CreateChatException('Чат с таким id уже существует в базе');
        }
    }
}
