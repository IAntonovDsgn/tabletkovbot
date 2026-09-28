<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateChangeMedicamentSelectedMedicamentHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private SessionRepositoryInterface $sessionRepository,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(RequestDTO $params): void
    {
        $medicamentId = null;
        $medicaments = $this->medicamentRepository->findByChatId($params->chatId);

        if (is_null($params->payload)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        } else {
            $buttonPayload = explode(MessageButton::PAYLOAD_SEPARATOR, $params->payload)[1];
        }

        foreach ($medicaments as $medicament) {
            if ((string) $medicament->getId() === $buttonPayload) {
                $medicamentId = $medicament->getId();
                break;
            }
        }

        if (is_null($medicamentId)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $session = $this->sessionRepository->findByChatId($params->chatId);

        if (is_null($session)) {
            throw new NotFoundEntityException(EnumMessageText::INTERNAL_ERROR->value);
        }

        $session->setPayload((string) $medicamentId);
        $this->sessionRepository->update($session);

        $this->outboxRepository->insert(
            Message::create(
                $session->getChatId(),
                EnumMessageText::WHAT_YOU_WANT_TO_CHANGE->value,
                [
                    new MessageButton(MessageButton::CHANGE_NAME, EnumState::CHANGE_MEDICAMENT_NAME_SELECTED),
                    new MessageButton(MessageButton::CHANGE_NOTIFICATION_TIME, EnumState::CHANGE_NOTIFICATION_TIME_SELECTED),
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ],
            )
        );
    }
}
