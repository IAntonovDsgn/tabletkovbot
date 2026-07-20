<?php

namespace App\Application\BotManager;

use App\Domain\Entities\Message\EnumMessageButton;
use App\Domain\Entities\Session\State\EnumSessionState;

final readonly class HandlerResponseDTO
{
    /**
     * @param EnumMessageButton[] $buttons
     */
    public function __construct(
       public ?string $text,
       public array $buttons,
       public EnumSessionState $nextState,
    ) {}
}
