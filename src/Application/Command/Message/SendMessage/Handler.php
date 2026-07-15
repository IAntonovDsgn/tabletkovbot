<?php

namespace App\Application\Command\Message\SendMessage;

use App\Domain\Message\Message;
use App\Domain\Message\MessageFacadeInterface;

final readonly class Handler
{
    public function __construct(
        private MessageFacadeInterface $messageFacade,
    ) {}

    public function handle(int $chatId, string $text, ?int $replyToMessageId = null): void
    {
        $message = new Message($chatId, $text, $replyToMessageId);
        $this->messageFacade->sendMessage($message);
    }
}
