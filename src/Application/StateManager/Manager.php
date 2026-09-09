<?php

declare(strict_types=1);

namespace App\Application\StateManager;

use App\Application\StateManager\DTOs\RequestDTO;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\Factories\StateHandlerFactory;
use App\Application\Outbox\OutboxRepositoryInterface;
use App\Application\UnitOfWork\UnitOfWorkInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\TransitionStateNotAllowedException;
use Throwable;

final readonly class Manager
{
    public function __construct(
        private StateHandlerFactory $factoryStateHandler,
        private SessionRepositoryInterface $sessionRepository,
        private OutboxRepositoryInterface $outboxRepository,
        private UnitOfWorkInterface $unitOfWork,
    ) {}

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
                $session,
                $params->messageText,
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
        } catch (InvalidValueException|TransitionStateNotAllowedException $e) {
            $this->unitOfWork->rollback();
            $this->notifyClientError($params->chatId, $e->getMessage());
            throw $e;
        } catch (Throwable $e) {
            $this->unitOfWork->rollback();
            $this->notifyInternalError($params->chatId);
            throw $e;
        }
    }

    private function notifyInternalError(int $chatId): void
    {
        try {
            $this->outboxRepository->insert(
                Message::create($chatId, EnumMessageText::INTERNAL_ERROR->value)
            );
        } catch (Throwable) {
        }
    }

    private function notifyClientError(int $chatId, ?string $message = null): void
    {
        $this->outboxRepository->insert(
            Message::create($chatId, $message ?? EnumMessageText::ERROR->value)
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
