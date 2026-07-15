<?php

namespace App\Application\Command\Session\TransitToState;

use App\Domain\Entities\Session\StateEnum;

final readonly class RequestDTO
{
    public function __construct(
        public int $chatId,
        public StateEnum $newState,
        public string $newValue = '',
    ) {
    }
}
