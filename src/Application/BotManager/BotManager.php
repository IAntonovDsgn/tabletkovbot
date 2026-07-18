<?php

namespace App\Application\BotManager;

use App\Domain\Entities\Message\EnumMessageButtonType;
use App\Domain\Entities\Message\EnumOutgoingMessageKey;
use App\Domain\Entities\Message\MessageInputDTO;
use App\Domain\Entities\Message\MessageOutputDTO;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Exceptions\BaseDomainException;
use App\Domain\Services\LogService\EnumLogTypes;
use App\Domain\Services\LogService\LogServiceInterface;

final readonly class BotManager
{
    public function __construct(
        private FactoryStateHandler $factoryStateHandler,
        private SessionRepositoryInterface $sessionRepository,
        private LogServiceInterface $logService
    ) {
    }

    public function process(MessageInputDTO $params): MessageOutputDTO
    {
        try {
            $session = $this->sessionRepository->findByChatId($params->chatId) ?? new Session($params->chatId);
            $stateHandler = $this->factoryStateHandler->make($session->getState());
            $stateHandlerResponseDTO = $stateHandler->handle(
                $params->value,
                $params->clickedButton,
                $session->getPayload()
            );
            $session->transitionToState($stateHandlerResponseDTO->nextState);
            $this->sessionRepository->save($session);
            return new MessageOutputDTO(
                $params->chatId,
                $stateHandlerResponseDTO->message,
                $stateHandlerResponseDTO->buttons
            );
        } catch (BaseDomainException $e) {
            return $this->errorHandler($params->chatId, $e->getMessage());
        } catch (\Exception $e) {
            $this->logService->addRecord(EnumLogTypes::ERROR->value, [$e->getMessage()]);
            return $this->errorHandler($params->chatId);
        }
    }

    public function errorHandler(int $chatId, ?string $message = null): MessageOutputDTO
    {
        $session = $this->sessionRepository->findByChatId($chatId);
        $session->resetState();
        return new MessageOutputDTO(
            $chatId,
            $message ?? EnumOutgoingMessageKey::ERROR->value,
            [
                EnumMessageButtonType::MAKE_INTAKE_MARK,
                EnumMessageButtonType::ADD_MEDICAMENT_BUTTON,
                EnumMessageButtonType::CHANGE_MEDICAMENT_BUTTON,
                EnumMessageButtonType::DELETE_MEDICAMENT,
                EnumMessageButtonType::DOWNLOAD_REPORT,
                EnumMessageButtonType::NOTIFICATIONS,
            ]
        );
    }
}
