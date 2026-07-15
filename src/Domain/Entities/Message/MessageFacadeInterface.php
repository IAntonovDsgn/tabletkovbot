<?php

namespace App\Domain\Entities\Message;

interface MessageFacadeInterface
{
    public function sendMessage(Message $message): void;

    /**
     * @return Message[]
     */
    public function getUpdates(): array;
}
