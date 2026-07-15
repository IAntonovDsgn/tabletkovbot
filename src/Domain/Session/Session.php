<?php

namespace App\Domain\Session;

final class Session
{
    private StateEnum $state;
    private readonly SessionStateMachine $sessionStateMachine;

    public function __construct(
        private readonly int $chatId,
    ) {
        $this->state = StateEnum::MENU;
        $this->sessionStateMachine = new SessionStateMachine();
    }

    public function getState(): StateEnum
    {
        return $this->state;
    }

    /**
     * @throws TransitionStateException
     */
    public function transitionToState(StateEnum $newState): void
    {
        if (! $this->sessionStateMachine->isTransitionToStateAllowed($newState, $this->state)) {
            throw new TransitionStateException('Transition state is not allowed');
        }
        $this->state = $newState;
    }
}
