<?php

declare(strict_types=1);

namespace App\Application\BotManager;

use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Report\Report;

final readonly class StateHandlerResponseDTO
{
    /**
     * @param MessageButton[] $buttons
     */
    public function __construct(
        public ?EnumMessageText $messageText,
        public array $buttons,
        public ?string $newSessionPayload = null,
        public ?Report $report = null,
    ) {
    }
}
