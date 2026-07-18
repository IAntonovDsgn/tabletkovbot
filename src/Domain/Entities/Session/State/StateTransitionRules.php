<?php

namespace App\Domain\Entities\Session\State;

class StateTransitionRules
{
    private const array ALLOWED_TRANSITIONS_FROM_TO = [
        EnumSessionState::MENU->value => [
            EnumSessionState::ADD_MEDICAMENT_SELECTED,
            EnumSessionState::CHANGE_MEDICAMENT_SELECTED,
            EnumSessionState::DELETE_MEDICAMENT_SELECTED,
            EnumSessionState::DOWNLOAD_REPORT_SELECTED,
            EnumSessionState::NOTIFICATIONS_SELECTED,
            EnumSessionState::MAKE_INTAKE_MARK_SELECTED,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::ADD_MEDICAMENT_SELECTED->value => [
            EnumSessionState::MENU,
            EnumSessionState::MEDICAMENT_NAME_ENTERED,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::MEDICAMENT_NAME_ENTERED->value => [
            EnumSessionState::MEDICAMENT_NOTIFICATION_TIME_ENTERED,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::MEDICAMENT_NOTIFICATION_TIME_ENTERED->value => [
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::CHANGE_MEDICAMENT_SELECTED->value => [
            EnumSessionState::CHANGE_MEDICAMENT_SELECTED_MEDICAMENT,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::CHANGE_MEDICAMENT_SELECTED_MEDICAMENT->value => [
            EnumSessionState::CHANGE_MEDICAMENT_NAME_SELECTED,
            EnumSessionState::CHANGE_NOTIFICATION_TIME_SELECTED,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::CHANGE_MEDICAMENT_NAME_SELECTED->value => [
            EnumSessionState::CHANGE_MEDICAMENT_NAME_ENTERED,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::CHANGE_NOTIFICATION_TIME_SELECTED->value => [
            EnumSessionState::CHANGE_NOTIFICATION_TIME_ENTERED,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::CHANGE_MEDICAMENT_NAME_ENTERED->value => [
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::CHANGE_NOTIFICATION_TIME_ENTERED->value => [
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::DELETE_MEDICAMENT_SELECTED->value => [
            EnumSessionState::DELETE_MEDICAMENT_SELECTED_MEDICAMENT,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::DELETE_MEDICAMENT_SELECTED_MEDICAMENT->value => [
            EnumSessionState::DELETE_MEDICAMENT_CONFIRMED,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::DOWNLOAD_REPORT_SELECTED->value => [
            EnumSessionState::DOWNLOAD_REPORT_DATES_SELECTED,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::DOWNLOAD_REPORT_DATES_SELECTED->value => [
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::NOTIFICATIONS_SELECTED->value => [
            EnumSessionState::NOTIFICATION_MODE_SELECTED,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::MAKE_INTAKE_MARK_SELECTED->value => [
            EnumSessionState::MAKE_INTAKE_MARK_MEDICAMENT_SELECTED,
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::MAKE_INTAKE_MARK_MEDICAMENT_SELECTED->value => [
            EnumSessionState::MENU,
            EnumSessionState::NOTIFIED,
        ],
        EnumSessionState::NOTIFIED->value => [
            EnumSessionState::INTAKE_MARK_HAS_MADE,
            EnumSessionState::MENU
        ],
        EnumSessionState::INTAKE_MARK_HAS_MADE->value => [
            EnumSessionState::MENU,
        ]
    ];

    public function isTransitionToStateAllowed(EnumSessionState $newState, EnumSessionState $oldState): bool
    {
        $result = false;
        if (in_array($newState, self::ALLOWED_TRANSITIONS_FROM_TO[$oldState->value], true))
        {
            $result = true;
        }
        return $result;
    }
}
