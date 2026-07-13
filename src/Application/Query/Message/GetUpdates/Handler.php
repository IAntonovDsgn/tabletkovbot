<?php

namespace App\Application\Query\Message\GetUpdates;

use App\Domain\Message\Message;
use App\Domain\Message\MessageFacadeInterface;

final readonly class Handler
{
    public function __construct(
        private MessageFacadeInterface $telegramMessageFacade
    ) {}

    /**
     * @return Message[]
     */
    public function handle(): array
    {
        return $this->telegramMessageFacade->getUpdates();
    }
}
