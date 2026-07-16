<?php

namespace App\Domain\Entities\Session;

use App\Domain\Exceptions\TransitionStateNotAllowedException;

final class Session
{
    private StateEnum $state;
    private string $value = '';
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
     * @throws TransitionStateNotAllowedException
     */
    public function transitionToState(StateEnum $newState): void
    {
        if (! $this->sessionStateMachine->isTransitionToStateAllowed($newState, $this->state)) {
            throw new TransitionStateNotAllowedException('Transition state is not allowed');
        }
        $this->state = $newState;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
