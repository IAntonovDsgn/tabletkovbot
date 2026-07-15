<?php

namespace App\Domain\Entities\Session;

class SessionStateMachine
{
    private const array ALLOWED_TRANSITIONS_FROM_TO = [
        StateEnum::MENU->value => [
            StateEnum::ADD_MEDICAMENT_SELECTED,
            StateEnum::CHANGE_MEDICAMENT_SELECTED,
            StateEnum::DELETE_MEDICAMENT_SELECTED,
            StateEnum::DOWNLOAD_REPORT_SELECTED,
            StateEnum::NOTIFICATIONS_SELECTED,
            StateEnum::MAKE_INTAKE_MARK_SELECTED,
            StateEnum::NOTIFIED,
        ],
        StateEnum::ADD_MEDICAMENT_SELECTED->value => [
            StateEnum::MENU,
            StateEnum::MEDICAMENT_NAME_ENTERED,
            StateEnum::NOTIFIED,
        ],
        StateEnum::MEDICAMENT_NAME_ENTERED->value => [
            StateEnum::MEDICAMENT_NOTIFICATION_TIME_ENTERED,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::MEDICAMENT_NOTIFICATION_TIME_ENTERED->value => [
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::CHANGE_MEDICAMENT_SELECTED->value => [
            StateEnum::CHANGE_MEDICAMENT_SELECTED_NAME,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::CHANGE_MEDICAMENT_SELECTED_NAME->value => [
            StateEnum::CHANGE_NAME_MEDICAMENT_SELECTED,
            StateEnum::CHANGE_NOTIFICATION_TIME_SELECTED,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::CHANGE_NAME_MEDICAMENT_SELECTED->value => [
            StateEnum::CHANGE_NAME_MEDICAMENT_ENTERED,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::CHANGE_NOTIFICATION_TIME_SELECTED->value => [
            StateEnum::CHANGE_NOTIFICATION_TIME_ENTERED,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::CHANGE_NAME_MEDICAMENT_ENTERED->value => [
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::CHANGE_NOTIFICATION_TIME_ENTERED->value => [
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::DELETE_MEDICAMENT_SELECTED->value => [
            StateEnum::DELETE_MEDICAMENT_SELECTED_NAME,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::DELETE_MEDICAMENT_SELECTED_NAME->value => [
            StateEnum::DELETE_MEDICAMENT_CONFIRMED,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::DOWNLOAD_REPORT_SELECTED->value => [
            StateEnum::DOWNLOAD_REPORT_DATES_SELECTED,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::DOWNLOAD_REPORT_DATES_SELECTED->value => [
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::NOTIFICATIONS_SELECTED->value => [
            StateEnum::NOTIFICATION_MODE_SELECTED,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::MAKE_INTAKE_MARK_SELECTED->value => [
            StateEnum::MAKE_INTAKE_MARK_MEDICAMENT_SELECTED,
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::MAKE_INTAKE_MARK_MEDICAMENT_SELECTED->value => [
            StateEnum::MENU,
            StateEnum::NOTIFIED,
        ],
        StateEnum::NOTIFIED->value => [
            StateEnum::INTAKE_MARK_HAVE_MADE,
            StateEnum::MENU
        ],
        StateEnum::INTAKE_MARK_HAVE_MADE->value => [
            StateEnum::MENU,
        ]
    ];

    public function isTransitionToStateAllowed(StateEnum $newState, StateEnum $oldState): bool
    {
        $result = false;
        if (in_array($newState, self::ALLOWED_TRANSITIONS_FROM_TO[$oldState->value], true))
        {
            $result = true;
        }
        return $result;
    }
}
