<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\StateManager\RequestDTO;

interface StateHandlerInterface
{
    public function handle(RequestDTO $params): void;
}
