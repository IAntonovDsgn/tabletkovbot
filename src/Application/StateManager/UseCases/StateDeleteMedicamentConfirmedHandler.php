<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\OutboxService\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateDeleteMedicamentConfirmedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
        private SessionRepositoryInterface $sessionRepository,
        private MessageOutboxRepositoryInterface $outboxRepository
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(RequestDTO $params): void {

        $session = $this->sessionRepository->findByChatId($params->chatId);

        if (is_null($session)) {
            throw new NotFoundEntityException(EnumMessageText::INTERNAL_ERROR->value);
        }

        $medicament = $this->medicamentRepository->findById((int) $session->getPayload());

        if (is_null($medicament) || ($medicament->getChatId() !== $session->getChatId())) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->deactivate();
        $this->medicamentRepository->update($medicament);

        $this->outboxRepository->insert(
            Message::create(
                $session->getChatId(),
                EnumMessageText::MEDICAMENT_DELETED->value,
                $this->keyboardFactory->makeMenuKeyboard()
            )
        );
    }
}
