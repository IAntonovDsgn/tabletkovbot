<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Keyboard\KeyboardFactory;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Exceptions\External\InvalidValueException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use App\Domain\Exceptions\Interior\RepositoryException;
use DateTimeImmutable;
use DateTimeZone;

final readonly class StateMedicamentNotificationTimeEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     * @throws RepositoryException
     */
    public function handle(
        int $chatId,
        ?string $messageText,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        if ($messageText === null) {
            throw new InvalidValueException(EnumMessageText::FORMAT_TIME_ERROR->value);
        }

        $notificationTime = DateTimeImmutable::createFromFormat(
            '!' . Medicament::TIME_FORMAT,
            $messageText,
            new DateTimeZone(Medicament::DATE_TIME_ZONE)
        );
        if ($notificationTime === false) {
            throw new InvalidValueException(EnumMessageText::FORMAT_TIME_ERROR->value);
        }

        $medicament = $this->medicamentRepository->findById((int)$sessionPayload);
        if (is_null($medicament) || $medicament->getChatId() !== $chatId) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->setNotificationTime($notificationTime);
        $this->medicamentRepository->update($medicament);
        return new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_ADDED_SUCCESS, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
