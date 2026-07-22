<?php

namespace App\Presentation\Api;

use App\Application\BotManager\Manager;
use App\Application\BotManager\RequestDTO;

final readonly class Controller
{
    public function __construct(
        private Manager $manager,
    )
    {}

    public function process(RequestDTO $request): void
    {
        $this->manager->process($request);
    }
}
