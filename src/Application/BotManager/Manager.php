<?php

namespace App\Application\BotManager;

use App\Application\Services\LogService\EnumLogTypes;
use App\Application\Services\LogService\LogServiceInterface;
use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\SendToClient\BaseSendToClientException;

final readonly class Manager
{
    public function __construct(
        private FactoryStateHandler $factoryStateHandler,
        private SessionRepositoryInterface $sessionRepository,
        private LogServiceInterface $logService,
        private MessageServiceInterface $messageService,
    ) {
    }

    public function process(RequestDTO $params): void
    {
        try {
            // TODO: start unit of work
            $session = $this->sessionRepository->findByChatId($params->chatId) ?? new Session($params->chatId);
            $newState = $params->newState ?? $session->getAllowedNextState();
            $session->transitionToState($newState);
            $handler = $this->factoryStateHandler->makeByState($newState);
            $handlerResponseDTO = $handler->handle(
                $params->chatId,
                $params->value,
                $session->getPayload(),
                $params->clickedButtonTitle,

            );
            $handlerResponseDTO->newSessionPayload && $session->setPayload($handlerResponseDTO->newSessionPayload);
            $this->sessionRepository->save($session);
            $this->messageService->sendMessage(
                new Message(
                    $params->chatId,
                    $handlerResponseDTO->messageText,
                    $handlerResponseDTO->buttons,
                )
            );
            // TODO: commit unit of work
        } catch (BaseSendToClientException $e) {
            // TODO: rollback unit of work
            $this->errorHandler($params->chatId, $e->getMessage());
        } catch (\Exception $e) {
            // TODO: rollback unit of work
            $this->logService->addRecord(EnumLogTypes::ERROR->value, [$e->getMessage()]);
            $this->errorHandler($params->chatId);
        }
    }

    public function errorHandler(int $chatId, ?string $message = null): void
    {
        try {
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
        } catch (\Exception $e) {
            $this->logService->addRecord(EnumLogTypes::ERROR->value, [$e->getMessage()]);
        }
    }
}
