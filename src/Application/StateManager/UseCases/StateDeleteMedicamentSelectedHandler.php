<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateDeleteMedicamentSelectedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(RequestDTO $params): void {
        $medicaments = $this->medicamentRepository->findByChatId($params->chatId);
        $buttons = [];

        if (empty($medicaments)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        foreach ($medicaments as $medicament) {
            if (! $medicament->isActive()) {
                continue;
            }
            $buttons[] = new MessageButton(
                $medicament->getName(),
                EnumState::SELECTED_MEDICAMENT_FOR_DELETE,
                (string) $medicament->getId(),
            );
        }

        $buttons[] = new MessageButton(MessageButton::MENU, EnumState::MENU);

        $this->outboxRepository->insert(
            Message::create(
                $params->chatId,
                EnumMessageText::CHOOSE_MEDICAMENT->value,
                $buttons
            )
        );
    }
}
