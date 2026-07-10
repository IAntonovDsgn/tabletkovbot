<?php

namespace App\Domain\TelegramMessage;

interface TelegramMessageFacadeInterface
{
    public function sendMessage(TelegramMessage $message): void;

    /**
     * @return TelegramMessage[]
     */
    public function getUpdates(): array;
}
