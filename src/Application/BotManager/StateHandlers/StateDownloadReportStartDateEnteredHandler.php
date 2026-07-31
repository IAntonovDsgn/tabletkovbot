<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Report\Report;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\External\InvalidValueException;
use DateTimeImmutable;

final readonly class StateDownloadReportStartDateEnteredHandler implements StateHandlerInterface
{

    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
    ) {
    }

    public function handle(int $chatId, ?string $text, ?string $sessionPayload, ?string $buttonPayload): StateHandlerResponseDTO
    {
        $startDate = DateTimeImmutable::createFromFormat(Report::DATE_FORMAT, $text)
            ?? throw new InvalidValueException(EnumMessageText::FORMAT_DATE_ERROR->value);

        $intakeMarks = $this->intakeMarkRepository->findByChatId($chatId);
        if (empty($intakeMarks)) {
            $result = new StateHandlerResponseDTO(
                EnumMessageText::INTAKE_MARKS_NOT_FOUND,
                [
                    new Button(Button::MAKE_INTAKE_MARK_BUTTON_TITLE, EnumState::MAKE_INTAKE_MARK_SELECTED),
                    new Button(Button::ADD_MEDICAMENT_BUTTON_TITLE, EnumState::ADD_MEDICAMENT_SELECTED),
                    new Button(Button::CHANGE_MEDICAMENT_BUTTON_TITLE, EnumState::CHANGE_MEDICAMENT_SELECTED),
                    new Button(Button::DELETE_MEDICAMENT_BUTTON_TITLE, EnumState::DELETE_MEDICAMENT_SELECTED),
                    new Button(Button::DOWNLOAD_REPORT_BUTTON_TITLE, EnumState::DOWNLOAD_REPORT_SELECTED),
                    new Button(Button::NOTIFICATIONS_BUTTON_TITLE, EnumState::NOTIFICATIONS_SELECTED),
                ]
            );
        } else {
            $report = new Report($startDate, $intakeMarks);
            $result = new StateHandlerResponseDTO(
                EnumMessageText::REPORT_READY,
                [
                    new Button(Button::MAKE_INTAKE_MARK_BUTTON_TITLE, EnumState::MAKE_INTAKE_MARK_SELECTED),
                    new Button(Button::ADD_MEDICAMENT_BUTTON_TITLE, EnumState::ADD_MEDICAMENT_SELECTED),
                    new Button(Button::CHANGE_MEDICAMENT_BUTTON_TITLE, EnumState::CHANGE_MEDICAMENT_SELECTED),
                    new Button(Button::DELETE_MEDICAMENT_BUTTON_TITLE, EnumState::DELETE_MEDICAMENT_SELECTED),
                    new Button(Button::DOWNLOAD_REPORT_BUTTON_TITLE, EnumState::DOWNLOAD_REPORT_SELECTED),
                    new Button(Button::NOTIFICATIONS_BUTTON_TITLE, EnumState::NOTIFICATIONS_SELECTED),
                ],
                report: $report
            );
        }

        return $result;
    }
}
