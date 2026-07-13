<?php

namespace App\Domain\Message;

interface MessageFacadeInterface
{
    public function sendMessage(Message $message): void;

    /**
     * @return Message[]
     */
    public function getUpdates(): array;
}
