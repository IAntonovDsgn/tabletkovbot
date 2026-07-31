<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\SendToClient\InvalidValueException;
use App\Domain\Exceptions\NotSendToClient\NotFoundEntityException;
use DateTimeImmutable;
use DateTimeZone;

final readonly class StateMedicamentNotificationTimeEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private SessionRepositoryInterface $sessionRepository,
    ) {
    }

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function handle(int $chatId, ?string $text, ?string $sessionPayload, ?string $buttonPayload): StateHandlerResponseDTO
    {
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
            EnumMessageText::MEDICAMENT_ADDED_SUCCESS,
            [
                new Button(Button::MAKE_INTAKE_MARK_BUTTON_TITLE, EnumState::MAKE_INTAKE_MARK_SELECTED),
                new Button(Button::ADD_MEDICAMENT_BUTTON_TITLE, EnumState::ADD_MEDICAMENT_SELECTED),
                new Button(Button::CHANGE_MEDICAMENT_BUTTON_TITLE, EnumState::CHANGE_MEDICAMENT_SELECTED),
                new Button(Button::DELETE_MEDICAMENT_BUTTON_TITLE, EnumState::DELETE_MEDICAMENT_SELECTED),
                new Button(Button::DOWNLOAD_REPORT_BUTTON_TITLE, EnumState::DOWNLOAD_REPORT_SELECTED),
                new Button(Button::NOTIFICATIONS_BUTTON_TITLE, EnumState::NOTIFICATIONS_SELECTED),
            ]
        );
    }
}
