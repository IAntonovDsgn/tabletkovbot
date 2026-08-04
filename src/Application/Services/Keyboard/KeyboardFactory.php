<?php

namespace App\Application\Services\Keyboard;

use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;

class KeyboardFactory
{
    /**
     * @return MessageButton[]
     */
    public function makeMenuKeyboard(): array
    {
        return [
            new MessageButton(MessageButton::MAKE_INTAKE_MARK_BUTTON_TITLE, EnumState::MAKE_INTAKE_MARK_SELECTED),
            new MessageButton(MessageButton::ADD_MEDICAMENT_BUTTON_TITLE, EnumState::ADD_MEDICAMENT_SELECTED),
            new MessageButton(MessageButton::CHANGE_MEDICAMENT_BUTTON_TITLE, EnumState::CHANGE_MEDICAMENT_SELECTED),
            new MessageButton(MessageButton::DELETE_MEDICAMENT_BUTTON_TITLE, EnumState::DELETE_MEDICAMENT_SELECTED),
            new MessageButton(MessageButton::DOWNLOAD_REPORT_BUTTON_TITLE, EnumState::DOWNLOAD_REPORT_SELECTED),
            new MessageButton(MessageButton::NOTIFICATIONS_BUTTON_TITLE, EnumState::NOTIFICATIONS_SELECTED),
        ];
    }
}
