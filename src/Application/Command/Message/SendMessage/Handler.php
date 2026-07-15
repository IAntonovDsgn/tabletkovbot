<?php

namespace App\Application\Command\Message\SendMessage;

use App\Domain\Chat\ChatRepositoryInterface;
use App\Domain\Message\Message;
use App\Domain\Message\MessageFacadeInterface;

final readonly class Handler
{
    public function __construct(
//        private ChatRepositoryInterface $chatRepository,
        private MessageFacadeInterface $messageFacade,
    ) {}

    /**
     * @throws SendMessageException
     */
    public function handle(int $chatId, string $text, ?int $replyToMessageId = null): void
    {
//        $chat = $this->chatRepository->findById($chatId);
//
//        if (is_null($chat)) {
//            throw new SendMessageException('Чат не найден');
//        }

        $message = new Message($chatId, $text, $replyToMessageId);
        $this->messageFacade->sendMessage($message);
    }
}
