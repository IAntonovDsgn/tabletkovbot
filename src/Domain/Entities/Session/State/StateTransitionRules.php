<?php

declare(strict_types=1);

namespace App\Domain\Entities\Session\State;

class StateTransitionRules
{
    public function isTransitionToStateAllowed(EnumState $newState, EnumState $oldState): bool
    {
        if (! isset(self::STRICKLY_ALLOWED_TRANSITIONS_TO_FROM[$newState->value])) {
            return true;
        }

        return in_array(
            $oldState,
            self::STRICKLY_ALLOWED_TRANSITIONS_TO_FROM[$newState->value],
            true
        );
    }

    /**
     * @return EnumState[]
     */
    public function getAllowedStates(EnumState $oldState): array
    {
        $result = [];

        /** @var string $newStateValue */
        foreach (self::STRICKLY_ALLOWED_TRANSITIONS_TO_FROM as $newStateValue => $oldStates) {
            if (in_array($oldState, $oldStates, true)) {
                $result[] = EnumState::from($newStateValue);
            }
        }

        return $result;
    }

    private const array STRICKLY_ALLOWED_TRANSITIONS_TO_FROM = [
        EnumState::MEDICAMENT_NAME_ENTERED->value => [
            EnumState::ADD_MEDICAMENT_SELECTED,
        ],
        EnumState::MEDICAMENT_NOTIFICATION_TIME_ENTERED->value => [
            EnumState::MEDICAMENT_NAME_ENTERED,
            EnumState::CHANGE_NOTIFICATION_TIME_SELECTED,
        ],
        EnumState::SELECTED_MEDICAMENT_FOR_CHANGE->value => [
            EnumState::CHANGE_MEDICAMENT_SELECTED,
        ],
        EnumState::CHANGE_MEDICAMENT_NAME_SELECTED->value => [
            EnumState::SELECTED_MEDICAMENT_FOR_CHANGE,
        ],
        EnumState::CHANGE_NOTIFICATION_TIME_SELECTED->value => [
            EnumState::SELECTED_MEDICAMENT_FOR_CHANGE,
        ],
        EnumState::CHANGE_MEDICAMENT_NAME_ENTERED->value => [
            EnumState::CHANGE_MEDICAMENT_NAME_SELECTED,
        ],
        EnumState::SELECTED_MEDICAMENT_FOR_DELETE->value => [
            EnumState::DELETE_MEDICAMENT_SELECTED,
        ],
        EnumState::DELETE_MEDICAMENT_CONFIRMED->value => [
            EnumState::SELECTED_MEDICAMENT_FOR_DELETE,
        ],
        EnumState::DOWNLOAD_REPORT_START_DATE_ENTERED->value => [
            EnumState::DOWNLOAD_REPORT_SELECTED,
        ],
        EnumState::INTAKE_MARK_HAS_MADE->value => [
            EnumState::MAKE_INTAKE_MARK_SELECTED,
            EnumState::NOTIFIED,
        ],
    ];
}
