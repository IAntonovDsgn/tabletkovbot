<?php

namespace App\Domain\Session;

final class Session
{
    public function __construct(
        private readonly int $chatId,
        private SessionStatusEnum $status = SessionStatusEnum::MENU,
    )
    {}

    public function getStatus(): SessionStatusEnum
    {
        return $this->status;
    }

    public function changeStatusToMenu(): void
    {
        $this->status = SessionStatusEnum::MENU;
    }

    public function changeStatusToAddMedicamentSelected(): void
    {
        $this->status = SessionStatusEnum::ADD_MEDICAMENT_SELECTED;
    }

    public function changeStatusToMedicamentNameEntered(): void
    {
        $this->status = SessionStatusEnum::MEDICAMENT_NAME_ENTERED;
    }

    public function changeStatusToMedicamentNotificationTimeEntered(): void
    {
        $this->status = SessionStatusEnum::MEDICAMENT_NOTIFICATION_TIME_ENTERED;
    }

    public function changeStatusToChangeMedicamentSelected(): void
    {
        $this->status = SessionStatusEnum::CHANGE_MEDICAMENT_SELECTED;
    }

    public function changeStatusToChangeMedicamentSelectedName(): void
    {
        $this->status = SessionStatusEnum::CHANGE_MEDICAMENT_SELECTED_NAME;
    }

    public function changeStatusToChangeNameMedicamentSelected(): void
    {
        $this->status = SessionStatusEnum::CHANGE_NAME_MEDICAMENT_SELECTED;
    }

    public function changeStatusToChangeNameMedicamentNameEntered(): void
    {
        $this->status = SessionStatusEnum::CHANGE_NAME_MEDICAMENT_ENTERED;
    }

    public function changeStatusToChangeNotificationTimeSelected(): void
    {
        $this->status = SessionStatusEnum::CHANGE_NOTIFICATION_TIME_SELECTED;
    }

    public function changeStatusToChangeNotificationTimeEntered(): void
    {
        $this->status = SessionStatusEnum::CHANGE_NOTIFICATION_TIME_ENTERED;
    }

    public function changeStatusToDeleteMedicamentSelected(): void
    {
        $this->status = SessionStatusEnum::DELETE_MEDICAMENT_SELECTED;
    }

    public function changeStatusToDeleteMedicamentSelectedName(): void
    {
        $this->status = SessionStatusEnum::DELETE_MEDICAMENT_SELECTED_NAME;
    }

    public function changeStatusToDeleteMedicamentConfirmed(): void
    {
        $this->status = SessionStatusEnum::DELETE_MEDICAMENT_CONFIRMED;
    }

    public function changeStatusToDownloadReportSelected(): void
    {
        $this->status = SessionStatusEnum::DOWNLOAD_REPORT_SELECTED;
    }

    public function changeStatusToDownloadReportDatesSelected(): void
    {
        $this->status = SessionStatusEnum::DOWNLOAD_REPORT_DATES_SELECTED;
    }

    public function changeStatusToNotificationsSelected(): void
    {
        $this->status = SessionStatusEnum::NOTIFICATIONS_SELECTED;
    }

    public function changeStatusToNotificationModeSelected(): void
    {
        $this->status = SessionStatusEnum::NOTIFICATION_MODE_SELECTED;
    }

    public function changeStatusToMakeIntakeMarkSelected(): void
    {
        $this->status = SessionStatusEnum::MAKE_INTAKE_MARK_SELECTED;
    }

    public function changeStatusToMakeIntakeMarkMedicamentSelected(): void
    {
        $this->status = SessionStatusEnum::MAKE_INTAKE_MARK_MEDICAMENT_SELECTED;
    }
}
