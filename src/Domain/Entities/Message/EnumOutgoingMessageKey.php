<?php

namespace  App\Domain\Entities\Message;

enum EnumOutgoingMessageKey: string {
    case MEDICAMENT_EMPTY_NAME_ERROR = 'Имя медикамента не может быть пустым, попробуйте снова';
    case MEDICAMENT_NOT_FOUND = 'Медикамент не найден, попробуйте снова';
    case MEDICAMENT_RENAMED_SUCCESS = 'Имя медикамента успешно изменено';
    case MENU = 'Добрый день! Чем я могу помочь?';
    case ERROR = 'Упс! Что-то пошло не так, давайте начнем сначала. Чем я могу помочь?';
}
