<?php

namespace App\Domain\Entities\Message;

enum EnumMessageButton: string
{
    case ADD_MEDICAMENT_BUTTON = 'Добавить медикамент';
    case CHANGE_MEDICAMENT_BUTTON = 'Изменить медикамент';
    case DELETE_MEDICAMENT = 'Удалить медикамент';
    case DOWNLOAD_REPORT = 'Скачать отчет';
    case NOTIFICATIONS = 'Уведомления';
    case MAKE_INTAKE_MARK = 'Принять медикамент';
}
