<?php

namespace App\Domain\Entities\Message;

use App\Domain\Entities\Session\State\EnumState;

class Button
{
    const string ADD_MEDICAMENT_BUTTON_TITLE = 'Добавить медикамент';
    const string CHANGE_MEDICAMENT_BUTTON_TITLE = 'Изменить медикамент';
    const string DELETE_MEDICAMENT_BUTTON_TITLE = 'Удалить медикамент';
    const string DOWNLOAD_REPORT_BUTTON_TITLE = 'Скачать отчет';
    const string NOTIFICATIONS_BUTTON_TITLE = 'Уведомления';
    const string MAKE_INTAKE_MARK_BUTTON_TITLE = 'Принять медикамент';
    const string CHANGE_NAME = 'Изменить имя';
    const string CHANGE_NOTIFICATION_TIME = 'Изменить время уведомления';
    const string MENU = 'Меню';
    const string CONFIRM = 'Подтвердить';
    const string DISABLE_NOTIFICATIONS = 'Отключить напоминания';
    const string ENABLE_NOTIFICATIONS = 'Включить напоминания';

    const string PAYLOAD_SEPARATOR = '|';

    public function __construct(
        private readonly string $title,
        private readonly EnumState $newState,
        private readonly ?string $additionalPayload = null,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getNewState(): string
    {
        return $this->newState->value;
    }

    public function getAdditionalPayload(): ?string
    {
        return $this->additionalPayload;
    }
}
