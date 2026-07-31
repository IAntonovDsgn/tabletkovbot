<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\NotSendToClient\SendMessageException;

final readonly class StateNotifiedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
    ) {
    }

    /**
     * @throws SendMessageException
     */
    public function handle(int $chatId, ?string $text, ?string $sessionPayload, ?string $buttonPayload): StateHandlerResponseDTO
    {
        $medicament = $this->medicamentRepository->findById($text);

        if (is_null($medicament)) {
            throw new SendMessageException('Medicament not found');
        }

        $intakeMark = new IntakeMark(
            $chatId,
            $medicament->getId(),
        );

        $this->intakeMarkRepository->save($intakeMark);

        return new StateHandlerResponseDTO(
            EnumMessageText::INTAKE_MARK_SAVED,
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
