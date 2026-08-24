<?php

declare(strict_types=1);

namespace App\Application\BotManager;

use App\Application\Outbox\OutboxRepositoryInterface;
use App\Application\UnitOfWork\UnitOfWorkInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\External\InvalidValueException;
use App\Domain\Exceptions\Interior\EntityAlreadyExistInPersistenceException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;
use App\Domain\Exceptions\Interior\TransitionStateNotAllowedException;
use Throwable;

final readonly class Manager
{
    public function __construct(
        private StateHandlerFactory $factoryStateHandler,
        private SessionRepositoryInterface $sessionRepository,
        private OutboxRepositoryInterface $outboxRepository,
        private KeyboardFactory $keyboardFactory,
        private UnitOfWorkInterface $unitOfWork,
    ) {
    }

    /**
     * @throws TransitionStateNotAllowedException
     * @throws Throwable
     * @throws InvalidValueException
     */
    public function process(RequestDTO $params): void
    {
        try {
            $this->unitOfWork->begin();

            $session = $this->sessionRepository->findByChatId($params->chatId) ?? Session::create($params->chatId);
            $requestPayloadArr = $params->payload
                ? explode(MessageButton::PAYLOAD_SEPARATOR, $params->payload)
                : [];
            $nextState = $this->getNextState($requestPayloadArr, $session);
            $stateHandler = $this->factoryStateHandler->makeByState($nextState);
            $session->transitionToState($nextState);

            $handlerResponseDTO = $stateHandler->handle(
                $params->chatId,
                $params->messageText,
                $session->getPayload(),
                $requestPayloadArr[1] ?? null,
            );

            if ($handlerResponseDTO->newSessionPayload) {
                $session->setPayload($handlerResponseDTO->newSessionPayload);
            }

            if ($session->isExistInPersistence()) {
                $this->sessionRepository->update($session);
            } else {
                $this->sessionRepository->insert($session);
            }

            $this->outboxRepository->insert(
                Message::create(
                    $params->chatId,
                    $handlerResponseDTO->messageText?->value,
                    $handlerResponseDTO->buttons
                )
            );

            $this->unitOfWork->commit();
        } catch (InvalidValueException $e) {
            $this->unitOfWork->rollback();
            $this->errorHandler($params->chatId, $e->getMessage());
        } catch (Throwable $e) {
            $this->unitOfWork->rollback();
            $this->notifyInternalError($params->chatId);
            throw $e;
        }
    }

    /**
     * Queues a user-facing apology for an unhandled failure. Best effort:
     * a failed notification must never mask the original exception.
     */
    private function notifyInternalError(int $chatId): void
    {
        try {
            $this->outboxRepository->insert(
                Message::create($chatId, EnumMessageText::INTERNAL_ERROR->value)
            );
        } catch (Throwable) {
        }
    }

    /**
     * @throws EntityAlreadyExistInPersistenceException
     * @throws RepositoryException
     * @throws NotFoundEntityException
     */
    private function errorHandler(int $chatId, ?string $message = null): void
    {
        $session = $this->sessionRepository->findByChatId($chatId);
        if ($session !== null) {
            $session->resetState();
            $this->sessionRepository->update($session);
        }

        $this->outboxRepository->insert(
            Message::create(
                $chatId,
                $message ?? EnumMessageText::ERROR->value,
                $this->keyboardFactory->makeMenuKeyboard()
            )
        );
    }

    /**
     * @param list<string> $requestPayload
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
