<?php

declare(strict_types=1);

namespace App\Domain\Entities\Session;

use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Entities\Session\State\StateTransitionRules;
use App\Domain\Exceptions\TransitionStateNotAllowedException;

final class Session
{
    private StateTransitionRules $stateMachineTransitionRules;

    private function __construct(
        private readonly ?int $id,
        private readonly int $chatId,
        private readonly bool $isExistInPersistence,
        private bool $isNotificationEnable,
        private ?string $payload,
        private EnumState $state
    ) {
        $this->stateMachineTransitionRules = new StateTransitionRules();
    }

    public static function create(
        int $chatId,
        bool $isNotificationEnable = true,
        ?string $payload = null,
        EnumState $state = EnumState::MENU,
    ): Session {
        return new self(
            null,
            $chatId,
            false,
            $isNotificationEnable,
            $payload,
            $state
        );
    }

    public static function restoreFromPersistence(
        int $id,
        int $chatId,
        bool $isNotificationEnable,
        EnumState $state,
        ?string $payload = null,
    ): Session {
        return new self(
            $id,
            $chatId,
            true,
            $isNotificationEnable,
            $payload,
            $state
        );
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
            throw new TransitionStateNotAllowedException(EnumMessageText::ERROR->value);
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

    public function isExistInPersistence(): bool
    {
        return $this->isExistInPersistence;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
