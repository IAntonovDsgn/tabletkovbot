<?php

namespace App\Application\Command\Session\TransitToState;

use App\Application\Command\Session\TransitToState\StateHandlers\ChangeNameMedicamentEnteredStateHandler;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\StateEnum;
use App\Domain\Entities\Session\StateHandlerInterface;
use App\Domain\Exceptions\TransitionStateException;
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
    public function handle(RequestDTO $requestData): void
    {
        $newState = $requestData->newState;
        $chatId = $requestData->chatId;
        $newStateInstance = $this->getStateClassInstance($newState);

        try {
            $this->sessionRepository->beginTransaction();
            $session = $this->sessionRepository->getByChatId($chatId) ?? new Session($chatId);
            $session->transitionToState($newState);

            if (! is_null($newStateInstance)) {
                $newStateInstance->handle();
            }

            $this->sessionRepository->save($session);
            $this->sessionRepository->commitTransaction();
        } catch (Throwable $e) {
            $this->sessionRepository->rollbackTransaction();
            throw new TransitionStateException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function getStateClassInstance(StateEnum $state): ?StateHandlerInterface
    {
        return match ($state) {
            StateEnum::CHANGE_NAME_MEDICAMENT_ENTERED => new ChangeNameMedicamentEnteredStateHandler(),
            default => null,
        };
    }
}
