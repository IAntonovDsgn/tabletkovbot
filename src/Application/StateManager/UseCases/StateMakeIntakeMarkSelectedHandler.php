<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateMakeIntakeMarkSelectedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(RequestDTO $params): void {
        $medicaments = $this->medicamentRepository->findByChatId($params->chatId);
        $buttons = [];

        if (empty($medicaments)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENTS_NOT_FOUND->value);
        }

        foreach ($medicaments as $medicament) {
            if ($medicament->isActive()) {
                $buttons[] = new MessageButton(
                    $medicament->getName(),
                    EnumState::INTAKE_MARK_HAS_MADE,
                    (string) $medicament->getId(),
                );
            }
        }

        if (empty($buttons)) {
            $this->outboxRepository->insert(
                Message::create(
                    $params->chatId,
                    EnumMessageText::NOT_FOUND_ACTIVE_MEDICAMENTS->value,
                    $this->keyboardFactory->makeMenuKeyboard()
                )
            );
        } else {
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
}
