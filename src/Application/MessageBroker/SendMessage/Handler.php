<?php

namespace App\Application\MessageBroker\SendMessage;

use App\Domain\Entities\Message\MessageButton;

class Handler
{
    /**
     * @param MessageButton[] $messageButtons
     */
    public function __invoke(int $chat_id, string $messageText, array $messageButtons): void
    {

    }

}
