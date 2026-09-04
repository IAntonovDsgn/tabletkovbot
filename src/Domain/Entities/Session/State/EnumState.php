<?php

declare(strict_types=1);

namespace App\Domain\Entities\Session\State;

enum EnumState: string
{
    case MENU = 'menu';
    case ADD_MEDICAMENT_SELECTED = 'add_medicament_selected';
    case MEDICAMENT_NAME_ENTERED = 'medicament_name_entered';
    case MEDICAMENT_NOTIFICATION_TIME_ENTERED = 'medicament_notification_time_entered';
    case CHANGE_MEDICAMENT_SELECTED = 'change_medicament_selected';
    case SELECTED_MEDICAMENT_FOR_CHANGE = 'selected_medicament_for_change';
    case CHANGE_MEDICAMENT_NAME_SELECTED = 'change_medicament_name_selected';
    case CHANGE_MEDICAMENT_NAME_ENTERED = 'change_medicament_name_entered';
    case CHANGE_NOTIFICATION_TIME_SELECTED = 'change_notification_time_selected';
    case DELETE_MEDICAMENT_SELECTED = 'delete_medicament_selected';
    case SELECTED_MEDICAMENT_FOR_DELETE = 'selected_medicament_for_delete';
    case DELETE_MEDICAMENT_CONFIRMED = 'delete_medicament_confirmed';
    case DOWNLOAD_REPORT_SELECTED = 'download_report_selected';
    case DOWNLOAD_REPORT_START_DATE_ENTERED = 'download_report_start_date_entered';
    case NOTIFICATIONS_SELECTED = 'notifications_selected';
    case NOTIFICATION_ENABLED = 'notification_enabled';
    case NOTIFICATION_DISABLED = 'notification_disabled';
    case MAKE_INTAKE_MARK_SELECTED = 'make_intake_mark_selected';
    case INTAKE_MARK_HAS_MADE = 'intake_mark_has_made';
    case NOTIFIED = 'notified';
}
