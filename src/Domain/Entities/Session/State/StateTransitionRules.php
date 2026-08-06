<?php

namespace App\Domain\Entities\Session\State;

class StateTransitionRules
{
    private const array ALLOWED_TRANSITIONS_FROM_TO = [
        EnumState::MENU->value => [
            EnumState::ADD_MEDICAMENT_SELECTED,
            EnumState::CHANGE_MEDICAMENT_SELECTED,
            EnumState::DELETE_MEDICAMENT_SELECTED,
            EnumState::DOWNLOAD_REPORT_SELECTED,
            EnumState::NOTIFICATIONS_SELECTED,
            EnumState::MAKE_INTAKE_MARK_SELECTED,
            EnumState::NOTIFIED,
            EnumState::MENU,
        ],
        EnumState::ADD_MEDICAMENT_SELECTED->value => [
            EnumState::MENU,
            EnumState::MEDICAMENT_NAME_ENTERED,
            EnumState::NOTIFIED,
        ],
        EnumState::MEDICAMENT_NAME_ENTERED->value => [
            EnumState::MEDICAMENT_NOTIFICATION_TIME_ENTERED,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::MEDICAMENT_NOTIFICATION_TIME_ENTERED->value => [
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::CHANGE_MEDICAMENT_SELECTED->value => [
            EnumState::SELECTED_MEDICAMENT_FOR_CHANGE,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::SELECTED_MEDICAMENT_FOR_CHANGE->value => [
            EnumState::CHANGE_MEDICAMENT_NAME_SELECTED,
            EnumState::CHANGE_NOTIFICATION_TIME_SELECTED,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::CHANGE_MEDICAMENT_NAME_SELECTED->value => [
            EnumState::CHANGE_MEDICAMENT_NAME_ENTERED,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::CHANGE_NOTIFICATION_TIME_SELECTED->value => [
            EnumState::MEDICAMENT_NOTIFICATION_TIME_ENTERED,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::CHANGE_MEDICAMENT_NAME_ENTERED->value => [
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::DELETE_MEDICAMENT_SELECTED->value => [
            EnumState::SELECTED_MEDICAMENT_FOR_CHANGE,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::SELECTED_MEDICAMENT_FOR_DELETE->value => [
            EnumState::DELETE_MEDICAMENT_CONFIRMED,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::DOWNLOAD_REPORT_SELECTED->value => [
            EnumState::DOWNLOAD_REPORT_START_DATE_ENTERED,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::DOWNLOAD_REPORT_START_DATE_ENTERED->value => [
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::NOTIFICATIONS_SELECTED->value => [
            EnumState::NOTIFICATION_ENABLED,
            EnumState::NOTIFICATION_DISABLED,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::NOTIFICATION_ENABLED->value => [
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::NOTIFICATION_DISABLED->value => [
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::MAKE_INTAKE_MARK_SELECTED->value => [
            EnumState::INTAKE_MARK_HAS_MADE,
            EnumState::MENU,
            EnumState::NOTIFIED,
        ],
        EnumState::NOTIFIED->value => [
            EnumState::INTAKE_MARK_HAS_MADE,
            EnumState::MENU
        ],
        EnumState::INTAKE_MARK_HAS_MADE->value => [
            EnumState::MENU,
        ]
    ];

    public function isTransitionToStateAllowed(EnumState $newState, EnumState $oldState): bool
    {
        $result = false;
        if (in_array($newState, self::ALLOWED_TRANSITIONS_FROM_TO[$oldState->value], true))
        {
            $result = true;
        }
        return $result;
    }


    /**
     * @return EnumState[]
     */
    public function getAllowedStates(EnumState $state): array
    {
        return self::ALLOWED_TRANSITIONS_FROM_TO[$state->value] ?? [];
    }
}
