<?php

namespace App\Domain\Entities\Session\State;

use App\Domain\Entities\Message\EnumMessageButtonType;

final readonly class StateHandlerResponseDTO
{
    /**
     * @param EnumMessageButtonType[] $buttons
     */
    public function __construct(
       public ?string $text,
       public array $buttons,
       public EnumSessionState $nextState,
    ) {}
}
