<?php

declare(strict_types=1);

namespace App\Application\BotManager;

use App\Domain\Entities\Session\Session;

interface StateHandlerInterface
{
    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload,
    ): StateHandlerResponseDTO;
}
