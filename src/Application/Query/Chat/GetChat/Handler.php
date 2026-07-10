<?php

namespace App\Application\Query\Chat\GetChat;

use App\Domain\Chat\Chat;
use App\Domain\Chat\ChatRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository
    ) {
    }

    public function getChatById($chatId): ?Chat
    {
        return $this->chatRepository->findById($chatId);
    }
}
