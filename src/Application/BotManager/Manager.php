<?php

namespace App\Application\BotManager;

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\KeyboardFactory;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\External\InvalidValueException;
use App\Domain\Exceptions\Interior\TransitionStateNotAllowedException;

final readonly class Manager
{
    public function __construct(
        private StateHandlerFactory $factoryStateHandler,
        private SessionRepositoryInterface $sessionRepository,
        private MessageServiceInterface $messageService,
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    /**
     * @throws TransitionStateNotAllowedException
     */
    public function process(RequestDTO $params): void
    {
        try {
            // TODO: start unit of work
            $session = $this->sessionRepository->findByChatId($params->chatId) ?? new Session($params->chatId);
            $requestPayloadArr = explode(Button::PAYLOAD_SEPARATOR, $params->payload);
            $nextState = $this->getNextState($requestPayloadArr, $params->chatId);
            $stateHandler = $this->factoryStateHandler->makeByState($nextState);

            $session->transitionToState($nextState);
            $handlerResponseDTO = $stateHandler->handle(
                $params->chatId,
                $params->text,
                $session->getPayload(),
                $requestPayloadArr[1] ?? null,
            );

            if ($handlerResponseDTO->newSessionPayload) {
                $session->setPayload($handlerResponseDTO->newSessionPayload);
            }

            $this->sessionRepository->save($session);
            $this->messageService->sendMessage(
                new Message($params->chatId, $handlerResponseDTO->messageText, $handlerResponseDTO->buttons)
            );
            // TODO: commit unit of work
        } catch (InvalidValueException $e) {
            $this->errorHandler($params->chatId, $e->getMessage());
        }
    }

    private function errorHandler(int $chatId, ?string $message = null): void
    {
        $session = $this->sessionRepository->findByChatId($chatId);
        $session->resetState();
        $this->messageService->sendMessage(
            new Message(
                $chatId,
                $message ?? EnumMessageText::ERROR, $this->keyboardFactory->makeMenuKeyboard()
            )
        );
    }

    /**
     * @throws TransitionStateNotAllowedException
     */
    private function getNextState(array $requestPayload, int $chatId): EnumState
    {
        if (!empty($requestPayload)) {
            return EnumState::tryFrom($requestPayload[0])
                ?? throw new TransitionStateNotAllowedException('Not found state in request payload');
        }

        $session = $this->sessionRepository->findByChatId($chatId);
        $filteredStates = array_values(array_filter(
            $session->getAllowedNextStates(),
            fn(EnumState $state) => $state !== EnumState::NOTIFIED && $state !== EnumState::MENU
        ));

        return match (count($filteredStates)) {
            0 => EnumState::MENU,
            1 => $filteredStates[0],
            default => throw new TransitionStateNotAllowedException('Cannot define next state'),
        };
    }
}
