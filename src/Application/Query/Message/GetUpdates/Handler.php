<?php

namespace App\Application\Query\Message\GetUpdates;

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\MessageOutputDTO;

final readonly class Handler
{
    public function __construct(
        private MessageServiceInterface $messageService
    ) {}

    /**
     * @return MessageOutputDTO[]
     */
    public function handle(): array
    {
        return $this->messageService->getUpdates();
    }
}
