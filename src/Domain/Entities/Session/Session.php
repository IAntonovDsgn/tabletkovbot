<?php

namespace App\Domain\Entities\Session;

use App\Domain\Entities\Session\State\EnumSessionState;
use App\Domain\Entities\Session\State\StateTransitionRules;
use App\Domain\Exceptions\TransitionStateNotAllowedException;

final class Session
{
    private StateTransitionRules $stateMachineTransitionRules;

    public function __construct(
        private readonly int $chatId,
        private bool $hasNotification = true,
        private ?string $payload = null,
        private EnumSessionState $state = EnumSessionState::MENU,
    ) {
        $this->stateMachineTransitionRules = new StateTransitionRules();
    }

    public function getState(): EnumSessionState
    {
        return $this->state;
    }

    /**
     * @throws TransitionStateNotAllowedException
     */
    public function transitionToState(EnumSessionState $newState): void
    {
        if (! $this->stateMachineTransitionRules->isTransitionToStateAllowed($newState, $this->state)) {
            throw new TransitionStateNotAllowedException('Transition state is not allowed');
        }
        $this->state = $newState;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function resetState(): void
    {
        $this->state = EnumSessionState::MENU;
        $this->payload = null;
    }

    public function enableNotifications(): void
    {
        $this->hasNotification = true;
    }

    public function disableNotifications(): void
    {
        $this->hasNotification = false;
    }
}
