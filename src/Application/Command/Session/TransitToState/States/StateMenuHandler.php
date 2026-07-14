<?php

namespace App\Application\Command\Session\TransitToState\States;

use App\Domain\Session\StateHandlerInterface;
use App\Infrastructure\Services\Message\MessageFacade;

final readonly class StateMenuHandler implements StateHandlerInterface
{
    public function handle(): void
    {
        $messageFacade = new MessageFacade();
        $messageFacade->sendMessage();
    }
}
