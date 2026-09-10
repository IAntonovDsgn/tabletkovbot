<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\OutboxService\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateSelectedMedicamentForDeleteHandler implements StateHandlerInterface
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(RequestDTO $params): void {

        if (is_null($params->payload)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        } else {
            $buttonPayload = explode(MessageButton::PAYLOAD_SEPARATOR, $params->payload)[1];
        }

        $session = $this->sessionRepository->findByChatId($params->chatId);

        if (is_null($session)) {
            throw new NotFoundEntityException(EnumMessageText::INTERNAL_ERROR->value);
        }

        $session->setPayload($buttonPayload);
        $this->sessionRepository->update($session);

        $this->outboxRepository->insert(
            Message::create(
                $params->chatId,
                EnumMessageText::ARE_YOU_CONFIRM_DELETE_MEDICAMENT->value,
                [
                    new MessageButton(MessageButton::CONFIRM, EnumState::DELETE_MEDICAMENT_CONFIRMED),
                    new MessageButton(MessageButton::CANCEL, EnumState::MENU),
                ],
            )
        );
    }
}
