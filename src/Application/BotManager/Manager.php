<?php

namespace App\Application\BotManager;

use App\Application\Services\LogService\EnumLogTypes;
use App\Application\Services\LogService\LogServiceInterface;
use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\EnumMessageButton;
use App\Domain\Entities\Message\EnumOutgoingText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Exceptions\BaseDomainException;

final readonly class Manager
{
    public function __construct(
        private FactoryStateHandler $factoryStateHandler,
        private SessionRepositoryInterface $sessionRepository,
        private LogServiceInterface $logService,
        private MessageServiceInterface $messageService,
    ) {
    }

    public function process(MessageInputDTO $params): void
    {
        try {
            // start unit of work
            $session = $this->sessionRepository->findByChatId($params->chatId) ?? new Session($params->chatId);
            $handler = $this->factoryStateHandler->makeByState($session->getState());
            $handlerResponseDTO = $handler->handle(
                $params->value,
                $params->clickedButton,
                $session->getPayload()
            );
            $session->transitionToState($handlerResponseDTO->nextState);
            $this->sessionRepository->save($session);
            $this->messageService->sendMessage(
                new Message(
                    $params->chatId,
                    $handlerResponseDTO->text,
                    $handlerResponseDTO->buttons
                )
            );
            // commit unit of work
        } catch (BaseDomainException $e) {
            // rollback unit of work
            $this->errorHandler($params->chatId, $e->getMessage());
        } catch (\Exception $e) {
            // rollback unit of work
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
                    $message ?? EnumOutgoingText::ERROR,
                    [
                        EnumMessageButton::MAKE_INTAKE_MARK,
                        EnumMessageButton::ADD_MEDICAMENT_BUTTON,
                        EnumMessageButton::CHANGE_MEDICAMENT_BUTTON,
                        EnumMessageButton::DELETE_MEDICAMENT,
                        EnumMessageButton::DOWNLOAD_REPORT,
                        EnumMessageButton::NOTIFICATIONS,
                    ]
                )
            );
        } catch (\Exception $e) {
            $this->logService->addRecord(EnumLogTypes::ERROR->value, [$e->getMessage()]);
        }
    }
}
