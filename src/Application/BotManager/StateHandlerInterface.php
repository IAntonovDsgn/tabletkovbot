<?php

namespace App\Application\BotManager;

use App\Domain\Entities\Message\EnumMessageButton;
use App\Domain\Exceptions\BaseDomainException;

interface StateHandlerInterface
{
    /**
     * @throws BaseDomainException
     */
    public function handle(
        ?string $text,
        ?EnumMessageButton $clickedButton,
        ?string $payload
    ): HandlerResponseDTO;
}
