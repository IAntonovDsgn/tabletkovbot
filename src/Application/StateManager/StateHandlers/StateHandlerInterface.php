<?php

declare(strict_types=1);

namespace App\Application\StateManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Domain\Entities\Session\Session;

interface StateHandlerInterface
{
    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload,
    ): StateHandlerResponseDTO;
}
