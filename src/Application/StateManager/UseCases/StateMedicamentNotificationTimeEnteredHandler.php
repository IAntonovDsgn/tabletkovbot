<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Exceptions\NotFoundEntityException;
use DateTimeImmutable;
use DateTimeZone;

final readonly class StateMedicamentNotificationTimeEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
        private SessionRepositoryInterface $sessionRepository,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function handle(RequestDTO $params): void {
        $notificationTime = DateTimeImmutable::createFromFormat(
            '!' . Medicament::TIME_FORMAT,
            $params->messageText ?? '',
            new DateTimeZone(Medicament::DATE_TIME_ZONE)
        );
        if ($notificationTime === false) {
            throw new InvalidValueException(EnumMessageText::FORMAT_TIME_ERROR->value);
        }

        $session = $this->sessionRepository->findByChatId($params->chatId);
        if (is_null($session)) {
            throw new NotFoundEntityException(EnumMessageText::INTERNAL_ERROR->value);
        }

        $medicament = $this->medicamentRepository->findById((int) $session->getPayload());
        if (is_null($medicament) || ($medicament->getChatId() !== $session->getChatId())) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->setNotificationTime($notificationTime);
        $this->medicamentRepository->update($medicament);

        $this->outboxRepository->insert(
            Message::create(
                $params->chatId,
                EnumMessageText::MEDICAMENT_ADDED_SUCCESS->value,
                $this->keyboardFactory->makeMenuKeyboard()
            )
        );
    }
}
