<?php

namespace App\Application\Command\Session\TransitToState;

use App\Application\Command\Session\TransitToState\States\StateMenuHandler;
use App\Domain\Session\Session;
use App\Domain\Session\SessionRepositoryInterface;
use App\Domain\Session\StateEnum;
use App\Domain\Session\StateHandlerInterface;
use App\Domain\Session\TransitionStateException;
use Throwable;

final readonly class Handler
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {
    }

    /**
     * @throws TransitionStateException
     */
    public function __invoke(int $chatId, StateEnum $receivedState): void
    {
        $newStateInstance = $this->getStateClassInstance($receivedState);

        try {
            $this->sessionRepository->beginTransaction();
            $session = $this->sessionRepository->getByChatId($chatId) ?? new Session($chatId);
            $session->transitionToState($receivedState);
            $newStateInstance->handle();
            $this->sessionRepository->save($session);
            $this->sessionRepository->commitTransaction();
        } catch (Throwable $e) {
            $this->sessionRepository->rollbackTransaction();
            throw new TransitionStateException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function getStateClassInstance(StateEnum $state): StateHandlerInterface
    {
        return match ($state) {
            StateEnum::MENU => new StateMenuHandler(),
        };
    }
}
