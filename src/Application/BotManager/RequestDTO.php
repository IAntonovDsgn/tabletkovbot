<?php

namespace App\Application\BotManager;

use App\Domain\Entities\Session\State\EnumState;

final readonly class RequestDTO
{
    public function __construct(
        public int $chatId,
        public ?string $value,
        public ?EnumState $newState,
        public ?string $clickedButtonTitle,
    ) {
    }
}
