<?php

namespace App\Application\Command\Message\SendMessage;

use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageFacadeInterface;

final readonly class Handler
{
    public function __construct(
        private MessageFacadeInterface $messageFacade,
    ) {}

    public function handle(ResponseDTO $responseData): void
    {
        $chatId = $responseData->chatId;
        $text = $responseData->text;
        $replyToMessageId = $responseData->replyToMessageId;

        $message = new Message($chatId, $text, $replyToMessageId);
        $this->messageFacade->sendMessage($message);
    }
}
