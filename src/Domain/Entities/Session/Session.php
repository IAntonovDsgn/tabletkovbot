<?php

namespace App\Domain\Entities\Session;

use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Entities\Session\State\StateTransitionRules;
use App\Domain\Exceptions\NotSendToClient\TransitionStateNotAllowedException;

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
     * @throws TransitionStateNotAllowedException
     */
    public function getAllowedNextState(): EnumState
    {
        $allowedStates = $this->stateMachineTransitionRules->getAllowedStates($this->state);

        if (empty($allowedStates)) {
            throw new TransitionStateNotAllowedException('Not found allowed states');
        } elseif (count($allowedStates) === 1 && $allowedStates[0] === EnumState::MENU) {
            $result = $allowedStates[0];
        } else {
            $statesWithoutMenu = array_diff($allowedStates, EnumState::MENU);
            if (count($statesWithoutMenu) === 1) {
                $result = $allowedStates[0];
            } else {
                throw new TransitionStateNotAllowedException('Count of allowed states is more than 1');
            }
        }

        return $result;
    }
}
