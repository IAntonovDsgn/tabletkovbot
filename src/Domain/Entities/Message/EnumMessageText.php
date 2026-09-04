<?php

declare(strict_types=1);

namespace App\Domain\Entities\Message;

enum EnumMessageText: string
{
    case CHOOSE_MEDICAMENT = 'Выберите медикамент';
    case ENTER_NEW_NAME = 'Введите новое имя медикамента:';
    case ENTER_TIME = 'Введите время уведомления в формате hh:mm:';
    case MEDICAMENT_EMPTY_NAME_ERROR = 'Имя медикамента не может быть пустым, попробуйте снова';
    case MEDICAMENT_NOT_FOUND = 'Медикамент не найден, попробуйте снова';
    case MEDICAMENT_RENAMED_SUCCESS = 'Имя медикамента успешно изменено. Чем еще я могу помочь?';
    case MENU = 'Добрый день! Чем я могу помочь?';
    case ERROR = "Упс! Что-то пошло не так. Пожалуйста обратитесь к администратору. \nЧем еще я могу помочь?";
    case FORMAT_TIME_ERROR = 'Введен неверный формат времени. Пожалуйста, введите время в формате hh:mm:';
    case MEDICAMENT_ADDED_SUCCESS = 'Медикамент сохранен! Чем еще я могу помочь?';
    case WHAT_YOU_WANT_TO_CHANGE = 'Что вы хотите изменить?';
    case ARE_YOU_CONFIRM_DELETE_MEDICAMENT = 'Вы уверены, что хотите удалить медикамент?';
    case MEDICAMENT_DELETED = 'Медикамент успешно удален. Чем еще я могу помочь?';
    case ENTER_DATE = 'Введите дату начала отчёта в формате dd.mm.yyyy';
    case FORMAT_DATE_ERROR = 'Введен неверный формат даты. Пожалуйста, введите дату в формате dd.mm.yyyy';
    case INTAKE_MARKS_NOT_FOUND = 'За выбранный период не найдены отметки о приеме медикаментов';
    case REPORT_READY = 'Отчет сформирован! Чем еще я могу помочь?';
    case NOTIFICATIONS_ENABLE = 'Напоминания о приеме медикаментов включены. Хотите отключить?';
    case NOTIFICATIONS_DISABLE = 'Напоминания о приеме медикаментов отключены. Хотите включить?';
    case SETTINGS_SAVED = 'Настройки сохранены! Чем еще я могу помочь?';
    case INTAKE_MARK_SAVED = 'Отметка о приеме сохранена! Чем еще я могу помочь?';
    case NOT_FOUND_ACTIVE_MEDICAMENTS = 'Упс, кажется у Вас нет активных медикаментов. Чем еще я могу помочь?';
    case INTERNAL_ERROR = 'Oops! Something broke. Please contact the administrator';

    public function isError(): bool
    {
        return match ($this) {
            self::ERROR,
            self::INTERNAL_ERROR,
            self::MEDICAMENT_EMPTY_NAME_ERROR,
            self::MEDICAMENT_NOT_FOUND,
            self::FORMAT_TIME_ERROR,
            self::FORMAT_DATE_ERROR => true,
            default => false,
        };
    }
}
