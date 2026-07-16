<?php

namespace App\Application\Command\Session\TransitToState;

use App\Application\Command\Session\TransitToState\StateHandlers\ChangeNameMedicamentEnteredStateHandler;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\StateEnum;
use App\Domain\Entities\Session\StateHandlerInterface;
use App\Domain\Exceptions\InvalidValueException;
use App\Domain\Exceptions\TransitionStateNotAllowedException;
use App\Infrastructure\Facade\ServiceContainer\ServiceContainer;

final readonly class Handler
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {
    }

    /**
     * @throws TransitionStateNotAllowedException
     * @throws InvalidValueException
     */
    public function handle(RequestDTO $requestData): void
    {
        $newState = $requestData->newState;
        $chatId = $requestData->chatId;
        $newSessionValue = $requestData->newSessionValue;
        $newStateInstance = $this->getStateClassInstance($newState);

        try {
            $this->sessionRepository->beginTransaction();
            $session = $this->sessionRepository->getByChatId($chatId) ?? new Session($chatId);
            $session->transitionToState($newState);

            if (! is_null($newStateInstance)) {
                $newStateInstance->handle($session->getValue(), $newSessionValue);
            }

            $this->sessionRepository->save($session);
            $this->sessionRepository->commitTransaction();
        } catch (\Exception $e) {
            $this->sessionRepository->rollbackTransaction();
            throw $e;
        }
    }

    private function getStateClassInstance(StateEnum $state): ?StateHandlerInterface
    {
        return match ($state) {
            StateEnum::CHANGE_NAME_MEDICAMENT_ENTERED => ServiceContainer::get(ChangeNameMedicamentEnteredStateHandler::class),
            default => null,
        };
    }
}
