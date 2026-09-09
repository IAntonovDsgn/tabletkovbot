<?php

declare(strict_types=1);

namespace App\Domain\Entities\Message;

use App\Domain\Entities\Session\States\EnumState;
use JsonSerializable;

class MessageButton implements JsonSerializable
{
    public const string ADD_MEDICAMENT_BUTTON_TITLE = 'Добавить медикамент';
    public const string CHANGE_MEDICAMENT_BUTTON_TITLE = 'Изменить медикамент';
    public const string DELETE_MEDICAMENT_BUTTON_TITLE = 'Удалить медикамент';
    public const string DOWNLOAD_REPORT_BUTTON_TITLE = 'Скачать отчет';
    public const string NOTIFICATIONS_BUTTON_TITLE = 'Напоминания';
    public const string MAKE_INTAKE_MARK_BUTTON_TITLE = 'Принять медикамент';
    public const string CHANGE_NAME = 'Изменить имя';
    public const string CHANGE_NOTIFICATION_TIME = 'Изменить время напоминаний';
    public const string MENU = 'Меню';
    public const string CANCEL = 'Отмена';
    public const string CONFIRM = 'Подтвердить';
    public const string DISABLE_NOTIFICATIONS = 'Отключить напоминания';
    public const string ENABLE_NOTIFICATIONS = 'Включить напоминания';
    public const string PAYLOAD_SEPARATOR = '|';

    public function __construct(
        private readonly string $title,
        private readonly EnumState $newState,
        private readonly ?string $additionalPayload = null,
    ) {}

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

    /**
     * @return array{title: string, new_state: string, additional_payload: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'new_state' => $this->newState->value,
            'additional_payload' => $this->additionalPayload,
        ];
    }
}
