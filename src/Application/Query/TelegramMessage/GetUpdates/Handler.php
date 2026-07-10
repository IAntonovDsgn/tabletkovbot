<?php

namespace App\Application\Query\TelegramMessage\GetUpdates;

use App\Domain\TelegramMessage\TelegramMessage;
use App\Domain\TelegramMessage\TelegramMessageFacadeInterface;

final readonly class Handler
{
    public function __construct(
        private TelegramMessageFacadeInterface $telegramMessageFacade
    ) {}

    /**
     * @return TelegramMessage[]
     */
    public function handle(): array
    {
        return $this->telegramMessageFacade->getUpdates();
    }
}
