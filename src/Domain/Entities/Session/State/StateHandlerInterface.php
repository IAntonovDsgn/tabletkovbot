<?php

namespace App\Domain\Entities\Session\State;

use App\Domain\Entities\Message\EnumMessageButtonType;
use App\Domain\Exceptions\BaseDomainException;

interface StateHandlerInterface
{
    /**
     * @throws BaseDomainException
     */
    public function handle(?string $text, ?EnumMessageButtonType $clickedButton, ?string $payload): StateHandlerResponseDTO;
}
