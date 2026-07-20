<?php

namespace App\Domain\Entities\Session\State;

enum EnumState: string {
    case MENU = 'menu';
    case ADD_MEDICAMENT_SELECTED = 'add_medicament_selected';
    case MEDICAMENT_NAME_ENTERED = 'medicament_name_entered';
    case MEDICAMENT_NOTIFICATION_TIME_ENTERED = 'medicament_notification_time_entered';
    case CHANGE_MEDICAMENT_SELECTED = 'change_medicament_selected';
    case CHANGE_MEDICAMENT_SELECTED_MEDICAMENT = 'change_medicament_selected_medicament';
    case CHANGE_MEDICAMENT_NAME_SELECTED = 'change_medicament_name_selected';
    case CHANGE_MEDICAMENT_NAME_ENTERED = 'change_medicament_name_entered';
    case CHANGE_NOTIFICATION_TIME_SELECTED = 'change_notification_time_selected';
    case CHANGE_NOTIFICATION_TIME_ENTERED = 'change_notification_time_entered';
    case DELETE_MEDICAMENT_SELECTED = 'delete_medicament_selected';
    case DELETE_MEDICAMENT_SELECTED_MEDICAMENT = 'delete_medicament_selected_medicament';
    case DELETE_MEDICAMENT_CONFIRMED = 'delete_medicament_confirmed';
    case DOWNLOAD_REPORT_SELECTED = 'download_report_selected';
    case DOWNLOAD_REPORT_DATES_SELECTED = 'download_report_dates_selected';
    case NOTIFICATIONS_SELECTED = 'notifications_selected';
    case NOTIFICATION_MODE_SELECTED = 'notification_mode_selected';
    case MAKE_INTAKE_MARK_SELECTED = 'make_intake_mark_selected';
    case MAKE_INTAKE_MARK_MEDICAMENT_SELECTED = 'make_intake_mark_medicament_selected';
    case INTAKE_MARK_HAS_MADE = 'intake_mark_have_made';
    case NOTIFIED = 'notified';
}
