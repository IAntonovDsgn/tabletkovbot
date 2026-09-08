<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\DTOs\StateHandlerResponseDTO;
use App\Application\BotManager\Exceptions\InvalidValueException;
use App\Application\BotManager\Factories\KeyboardFactory;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use App\Domain\Exceptions\NotFoundEntityException;
use DateTimeImmutable;
use DateTimeZone;

final readonly class StateMedicamentNotificationTimeEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $notificationTime = DateTimeImmutable::createFromFormat(
            '!' . Medicament::TIME_FORMAT,
            $messageText ?? '',
            new DateTimeZone(Medicament::DATE_TIME_ZONE)
        );
        if ($notificationTime === false) {
            throw new InvalidValueException(EnumMessageText::FORMAT_TIME_ERROR->value);
        }

        $medicament = $this->medicamentRepository->findById((int) $session->getPayload());
        if (is_null($medicament) || $medicament->getChatId() !== $session->getChatId()) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->setNotificationTime($notificationTime);
        $this->medicamentRepository->update($medicament);
        return new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_ADDED_SUCCESS,
            $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
