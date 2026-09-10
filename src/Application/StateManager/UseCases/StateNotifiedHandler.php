<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\OutboxService\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class StateNotifiedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
        private KeyboardFactory $keyboardFactory,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    /**
     * @throws NotFoundEntityException
     */
    public function handle(RequestDTO $params ): void {

        if (is_null($params->payload)) {
            $medicamentId = $params->messageText;
        } else {
            $medicamentId = explode(MessageButton::PAYLOAD_SEPARATOR, $params->payload)[1];
        }

        if ($medicamentId === null) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament = $this->medicamentRepository->findById((int) $medicamentId);

        if (is_null($medicament) || $medicament->getChatId() !== $params->chatId) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $this->intakeMarkRepository->insert(
            IntakeMark::create($params->chatId, (int) $medicament->getId())
        );

        $this->outboxRepository->insert(
            Message::create(
                $params->chatId,
                EnumMessageText::INTAKE_MARK_SAVED->value,
                $this->keyboardFactory->makeMenuKeyboard()
            )
        );
    }
}
