<?php

namespace App\Application\BotManager;

use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Report\Report;

final readonly class StateHandlerResponseDTO
{
    /**
     * @param Button[] $buttons
     */
    public function __construct(
        public ?EnumMessageText $messageText,
        public array $buttons,
        public ?string $newSessionPayload = null,
        public ?Report $report = null,
    ) {
    }
}
