<?php

namespace App\Domain\Session;

enum SessionStatusEnum:string {
    case MENU = 'menu';
    case ADD_MEDICAMENT_SELECTED = 'add medicament selected';
    case MEDICAMENT_NAME_ENTERED = 'medicament name entered';
    case MEDICAMENT_NOTIFICATION_TIME_ENTERED = 'medicament notification time entered';
    case CHANGE_MEDICAMENT_SELECTED = 'change medicament selected';
    case CHANGE_MEDICAMENT_SELECTED_NAME = 'change medicament selected name';
    case CHANGE_NAME_MEDICAMENT_SELECTED = 'change name medicament selected';
    case CHANGE_NAME_MEDICAMENT_ENTERED = 'change name medicament';
    case CHANGE_NOTIFICATION_TIME_SELECTED = 'change notification time selected';
    case CHANGE_NOTIFICATION_TIME_ENTERED = 'change notification time entered';
    case DELETE_MEDICAMENT_SELECTED = 'delete medicament selected';
    case DELETE_MEDICAMENT_SELECTED_NAME = 'delete medicament selected name';
    case DELETE_MEDICAMENT_CONFIRMED = 'delete medicament confirmed';
    case DOWNLOAD_REPORT_SELECTED = 'download report selected';
    case DOWNLOAD_REPORT_DATES_SELECTED = 'download report dates selected';
    case NOTIFICATIONS_SELECTED = 'notifications selected';
    case NOTIFICATION_MODE_SELECTED = 'notification mode selected';
    case MAKE_INTAKE_MARK_SELECTED = 'make intake mark selected';
    case MAKE_INTAKE_MARK_MEDICAMENT_SELECTED = 'make intake mark medicament selected';
    case INTAKE_MARK_HAVE_MADE = 'intake mark have made';
}
