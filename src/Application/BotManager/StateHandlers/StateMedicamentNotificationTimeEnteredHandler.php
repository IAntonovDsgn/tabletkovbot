<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Exceptions\External\InvalidValueException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use DateTimeImmutable;
use DateTimeZone;

final readonly class StateMedicamentNotificationTimeEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private SessionRepositoryInterface $sessionRepository,
        private KeyboardFactory $keyboardFactory,
    ) {
    }

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function handle(
        int $chatId,
        ?string $text,
        ?string $sessionPayload,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        $notificationTime = DateTimeImmutable::createFromFormat(
            Medicament::TIME_FORMAT,
            $text,
            new DateTimeZone(Medicament::DATE_TIME_ZONE)
        ) ?? throw new InvalidValueException(EnumMessageText::FORMAT_TIME_ERROR->value);

        $session = $this->sessionRepository->findByChatId($chatId);
        $medicamentId = $session->getPayload();
        $medicament = $this->medicamentRepository->findById($medicamentId);

        if (is_null($medicament)) {
            throw new NotFoundEntityException(EnumMessageText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->setNotificationTime($notificationTime);
        $this->medicamentRepository->save($medicament);
        return new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_ADDED_SUCCESS, $this->keyboardFactory->makeMenuKeyboard()
        );
    }
}
