<?php

declare(strict_types=1);

namespace App\Application\StateManager;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\Factories\StateHandlerFactory;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;
use App\Domain\Exceptions\TransitionStateNotAllowedException;
use App\Domain\UnitOfWorkInterface;
use App\Infrastructure\Dbal\Exceptions\AlreadyExistInPersistenceException;

final readonly class Manager
{
    public function __construct(
        private StateHandlerFactory $factoryStateHandler,
        private SessionRepositoryInterface $sessionRepository,
        private MessageOutboxRepositoryInterface $outboxRepository,
        private UnitOfWorkInterface $unitOfWork,
        private KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * @throws TransitionStateNotAllowedException
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     * @throws AlreadyExistInPersistenceException
     */
    public function process(RequestDTO $params): void
    {
        try {
            $this->unitOfWork->begin();
            $newState = $this->transitSessionToNextState($params->chatId, $params->payload);
            $stateHandler = $this->factoryStateHandler->makeByState($newState);
            $stateHandler->handle($params);
            $this->unitOfWork->commit();
        } catch (InvalidValueException|TransitionStateNotAllowedException|NotFoundEntityException|AlreadyExistInPersistenceException $e) {
            $this->unitOfWork->rollback();
            $this->notifyClientError($params->chatId, $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->unitOfWork->rollback();
            $this->notifyInternalError($params->chatId);
            throw $e;
        }
    }

    /**
     * @throws InvalidValueException
     * @throws TransitionStateNotAllowedException
     */
    private function transitSessionToNextState(int $chatId, ?string $payload = null): EnumState
    {
        $session = $this->sessionRepository->findByChatId($chatId) ?? Session::create($chatId);
        $requestPayloadArr = $payload
            ? explode(MessageButton::PAYLOAD_SEPARATOR, $payload)
            : [];
        $nextState = $this->getNextState($requestPayloadArr, $session);

        $session->transitionToState($nextState);

        if ($session->isExistInPersistence()) {
            $this->sessionRepository->update($session);
        } else {
            $this->sessionRepository->insert($session);
        }

        return $nextState;
    }

    private function notifyInternalError(int $chatId): void
    {
        try {
            $this->outboxRepository->insert(
                Message::create($chatId, EnumMessageText::INTERNAL_ERROR->value)
            );
        } catch (\Exception) {
        }
    }

    private function notifyClientError(int $chatId, ?string $message = null): void
    {
        $this->outboxRepository->insert(
            Message::create(
                $chatId,
                    $message ?? EnumMessageText::ERROR->value,
                $this->keyboardFactory->makeMenuKeyboard()
            )
        );
    }

    /**
     * @param string[] $requestPayload
     * @throws InvalidValueException
     */
    private function getNextState(array $requestPayload, Session $session): EnumState
    {
        if (!empty($requestPayload)) {
            return EnumState::tryFrom($requestPayload[0])
                ?? throw new InvalidValueException(EnumMessageText::ERROR->value);
        }

        $filteredStates = array_values(
            array_filter(
                $session->getAllowedNextStates(),
                fn(EnumState $state) => $state !== EnumState::NOTIFIED && $state !== EnumState::MENU
            )
        );

        if (count($filteredStates) === 1) {
            $result = $filteredStates[0];
        } else {
            $result = EnumState::MENU;
        }

        return $result;
    }
}
