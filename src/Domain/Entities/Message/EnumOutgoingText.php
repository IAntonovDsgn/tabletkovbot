<?php

namespace  App\Domain\Entities\Message;

enum EnumOutgoingText: string {
    case CHOOSE_MEDICAMENT = 'Выберите медикамент';
    case ENTER_NEW_NAME = 'Введите новое имя медикамента';
    case ENTER_NOTIFICATION_TIME = 'Введите время напоминания в формате hh:mm';
    case MEDICAMENT_EMPTY_NAME_ERROR = 'Имя медикамента не может быть пустым, попробуйте снова';
    case MEDICAMENT_NOT_FOUND = 'Медикамент не найден, попробуйте снова';
    case MEDICAMENT_RENAMED_SUCCESS = 'Имя медикамента успешно изменено. Чем еще я могу помочь?';
    case MENU = 'Добрый день! Чем я могу помочь?';
    case ERROR = 'Упс! Что-то пошло не так, давайте начнем сначала. Чем я могу помочь?';
    case FORMAT_TIME_ERROR = 'Введен неверный формат времени. Пожалуйста, введите время в формате hh:mm';
    case MEDICAMENT_ADDED_SUCCESS = 'Медикамент сохранен! Чем еще я могу помочь?';
    case WHAT_YOU_WANT_TO_CHANGE = 'Что вы хотите изменить?';
}
