<?php

declare(strict_types=1);

namespace App\Application\Message\SendMessage;

use App\Application\Message\MessageServiceInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;

final readonly class Handler
{
    public function __construct(
        private MessageServiceInterface $messageService,
    ) {
    }

    /**
     * @param MessageButton[] $messageButtons
     */
    public function handle(int $chat_id, string $messageText, array $messageButtons = []): void
    {
        $message = Message::create($chat_id, $messageText, $messageButtons);
        $this->messageService->sendMessage($message);
    }
}
