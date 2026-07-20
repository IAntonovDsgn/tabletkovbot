<?php

namespace App\Presentation\Api;

use App\Application\BotManager\Manager;
use App\Domain\Entities\Message\MessageInputDTO;

final readonly class Controller
{
    public function __construct(
        private Manager $manager,
    )
    {}

    public function process(MessageInputDTO $request): void
    {
        $this->manager->process($request);
    }
}
