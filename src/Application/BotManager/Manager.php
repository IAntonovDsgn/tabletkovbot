<?php

namespace App\Application\BotManager;

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\Interior\TransitionStateNotAllowedException;
use Exception;

final readonly class Manager
{
    public function __construct(
        private StateHandlerFactory $factoryStateHandler,
        private SessionRepositoryInterface $sessionRepository,
        private MessageServiceInterface $messageService,
    ) {
    }

    /**
     * @throws Exception
     * @throws TransitionStateNotAllowedException
     */
    public function process(RequestDTO $params): void
    {
        try {
            // TODO: start unit of work
            $session = $this->sessionRepository->findByChatId($params->chatId) ?? new Session($params->chatId);
            $requestPayloadArr = explode(Button::PAYLOAD_SEPARATOR, $params->payload);
            $newState = $this->getNewState($requestPayloadArr);
            $stateHandler = $this->factoryStateHandler->makeByState($newState);
            $buttonPayload = $requestPayloadArr[1] ?? null;

            $session->transitionToState($newState);
            $handlerResponseDTO = $stateHandler->handle(
                $params->chatId,
                $params->text,
                $session->getPayload(),
                $buttonPayload,
            );

            $handlerResponseDTO->newSessionPayload && $session->setPayload($handlerResponseDTO->newSessionPayload);
            $this->sessionRepository->save($session);
            $this->messageService->sendMessage(
                new Message($params->chatId, $handlerResponseDTO->messageText, $handlerResponseDTO->buttons)
            );
            // TODO: commit unit of work
        } catch (TransitionStateNotAllowedException $e) {
            // TODO: rollback unit of work
            $this->errorHandler($params->chatId, $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            // TODO: rollback unit of work
            $this->errorHandler($params->chatId);
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    private function errorHandler(int $chatId, ?string $message = null): void
    {
        $session = $this->sessionRepository->findByChatId($chatId);
        $session->resetState();
        $this->messageService->sendMessage(
            new Message(
                $chatId,
                $message ?? EnumMessageText::ERROR,
                [
                    new Button(Button::MAKE_INTAKE_MARK_BUTTON_TITLE, EnumState::MAKE_INTAKE_MARK_SELECTED),
                    new Button(Button::ADD_MEDICAMENT_BUTTON_TITLE, EnumState::ADD_MEDICAMENT_SELECTED),
                    new Button(Button::CHANGE_MEDICAMENT_BUTTON_TITLE, EnumState::CHANGE_MEDICAMENT_SELECTED),
                    new Button(Button::DELETE_MEDICAMENT_BUTTON_TITLE, EnumState::DELETE_MEDICAMENT_SELECTED),
                    new Button(Button::DOWNLOAD_REPORT_BUTTON_TITLE, EnumState::DOWNLOAD_REPORT_SELECTED),
                    new Button(Button::NOTIFICATIONS_BUTTON_TITLE, EnumState::NOTIFICATIONS_SELECTED),
                ],
            )
        );
    }

    /**
     * @throws TransitionStateNotAllowedException
     */
    private function getNewState(array $requestPayload): EnumState
    {
        if (count($requestPayload) < 1) {
            throw new TransitionStateNotAllowedException('Not found request payload');
        } elseif (EnumState::tryFrom($requestPayload[0]) !== null) {
            throw new TransitionStateNotAllowedException('Not found state in request payload');
        } else {
            $newState = EnumState::tryFrom($requestPayload[0]);
        }
        return $newState;
    }
}
