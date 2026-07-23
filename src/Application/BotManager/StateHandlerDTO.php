<?php

namespace App\Application\BotManager;

use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumOutgoingText;

final readonly class StateHandlerDTO
{
    /**
     * @param Button[] $buttons
     */
    public function __construct(
        public ?EnumOutgoingText $messageText,
        public array $buttons,
        public ?string $newSessionPayload = null,
    ) {
    }
}
