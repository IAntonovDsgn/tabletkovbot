<?php

namespace App\Domain\Entities\Session;

use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Entities\Session\State\StateTransitionRules;
use App\Domain\Exceptions\Interior\TransitionStateNotAllowedException;

final class Session
{
    private StateTransitionRules $stateMachineTransitionRules;

    public function __construct(
        private readonly int $chatId,
        private bool $isNotificationEnable = true,
        private ?string $payload = null,
        private EnumState $state = EnumState::MENU
    ) {
        $this->stateMachineTransitionRules = new StateTransitionRules();
    }

    public function getState(): EnumState
    {
        return $this->state;
    }

    public function setPayload(?string $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * @throws TransitionStateNotAllowedException
     */
    public function transitionToState(EnumState $newState): void
    {
        if (!$this->stateMachineTransitionRules->isTransitionToStateAllowed($newState, $this->state)) {
            throw new TransitionStateNotAllowedException('Transition state is not allowed');
        }
        $this->state = $newState;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function resetState(): void
    {
        $this->state = EnumState::MENU;
        $this->payload = null;
    }

    public function enableNotifications(): void
    {
        $this->isNotificationEnable = true;
    }

    public function disableNotifications(): void
    {
        $this->isNotificationEnable = false;
    }

    public function isNotificationEnabled(): bool
    {
        return $this->isNotificationEnable;
    }

    /**
     * @return EnumState[]
     */
    public function getAllowedNextStates(): array
    {
        return $this->stateMachineTransitionRules->getAllowedStates($this->state);
    }
}
