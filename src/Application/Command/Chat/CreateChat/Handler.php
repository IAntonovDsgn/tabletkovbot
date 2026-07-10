<?php

namespace App\Application\Command\Chat\CreateChat;

use App\Domain\Chat\ChatFactory;
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
        $chat = $this->chatRepository->findById($chatId);

        if (is_null($chat)) {
            ChatFactory::create($chatId);
        } else {
            throw new CreateChatException('Чат с таким id уже существует в базе');
        }
    }
}
