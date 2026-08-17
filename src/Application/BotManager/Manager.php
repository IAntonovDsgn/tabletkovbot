<?php

namespace App\Application\BotManager;

use App\Application\Persistence\OutboxRepositoryInterface;
use App\Application\Persistence\UnitOfWorkInterface;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\External\InvalidValueException;
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

    public function process(RequestDTO $params): void
    {
        try {
            $this->unitOfWork->begin();
            $session = $this->sessionRepository->findByChatId($params->chatId) ?? new Session($params->chatId);
            $requestPayloadArr = $params->payload
                ? explode(MessageButton::PAYLOAD_SEPARATOR, $params->payload)
                : [];
            $nextState = $this->getNextState($requestPayloadArr, $session);
            $stateHandler = $this->factoryStateHandler->makeByState($nextState);
            $session->transitionToState($nextState);
            $this->sessionRepository->save($session);

            $handlerResponseDTO = $stateHandler->handle(
                $params->chatId,
                $params->text,
                $session->getPayload(),
                $requestPayloadArr[1] ?? null,
            );

            $session = $this->sessionRepository->findByChatId($params->chatId) ?? new Session($params->chatId);
            if ($handlerResponseDTO->newSessionPayload) {
                $session->setPayload($handlerResponseDTO->newSessionPayload);
            }
            $this->sessionRepository->save($session);

            $this->outboxRepository->save(
                new Message(
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
            throw $e;
        }
    }

    private function errorHandler(int $chatId, ?string $message = null): void
    {
        $session = $this->sessionRepository->findByChatId($chatId);
        if ($session !== null) {
            $session->resetState();
            $this->sessionRepository->save($session);
        }

        $this->outboxRepository->save(
            new Message(
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

        $filteredStates = array_values(array_filter(
            $session->getAllowedNextStates(),
            fn(EnumState $state) => $state !== EnumState::NOTIFIED && $state !== EnumState::MENU
        ));

        if (count($filteredStates) === 1) {
            $result = $filteredStates[0];
        } else {
            $result = EnumState::MENU;
        }

        return $result;
    }
}
