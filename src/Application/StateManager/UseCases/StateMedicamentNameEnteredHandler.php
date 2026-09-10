<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\OutboxService\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateMedicamentNameEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private SessionRepositoryInterface $sessionRepository,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function handle(RequestDTO $params): void {
        if (is_null($params->messageText)) {
            throw new InvalidValueException(EnumMessageText::MEDICAMENT_EMPTY_NAME_ERROR->value);
        }

        $medicament = Medicament::create($params->messageText, $params->chatId);
        $medicamentId = $this->medicamentRepository->insert($medicament);
        $session = $this->sessionRepository->findByChatId($params->chatId);

        if (is_null($session)) {
            throw new NotFoundEntityException(EnumMessageText::INTERNAL_ERROR->value);
        }

        $session->setPayload((string) $medicamentId);
        $this->sessionRepository->update($session);

        $this->outboxRepository->insert(
            Message::create(
                $params->chatId,
                EnumMessageText::ENTER_TIME->value,
                [
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            )
        );
    }
}
