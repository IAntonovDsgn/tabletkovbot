<?php

namespace App\Application\Query\Message\GetUpdates;

use App\Application\Services\MessageService\MessageServiceInterface;

final readonly class Handler
{
    public function __construct(
        private MessageServiceInterface $messageService
    ) {}

    public function handle(): array
    {
        return $this->messageService->getUpdates();
    }
}
