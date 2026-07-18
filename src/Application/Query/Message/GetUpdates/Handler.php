<?php

namespace App\Application\Query\Message\GetUpdates;

use App\Domain\Entities\Message\MessageServiceInterface;
use App\Domain\Entities\Message\MessageOutputDTO;

final readonly class Handler
{
    public function __construct(
        private MessageServiceInterface $messageFacade
    ) {}

    /**
     * @return MessageOutputDTO[]
     */
    public function handle(): array
    {
        return $this->messageFacade->getUpdates();
    }
}
