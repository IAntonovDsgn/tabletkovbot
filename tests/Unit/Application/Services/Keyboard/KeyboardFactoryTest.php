<?php

namespace Tests\Unit\Application\Services\Keyboard;

use App\Application\StateManager\Factories\KeyboardFactory;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use PHPUnit\Framework\TestCase;

class KeyboardFactoryTest extends TestCase
{
    public function testMakeMenuKeyboard(): void
    {
        $factory = new KeyboardFactory();
        $keyboard = $factory->makeMenuKeyboard();

        $expectedKeyboard = [
            new MessageButton(MessageButton::MAKE_INTAKE_MARK_BUTTON_TITLE, EnumState::MAKE_INTAKE_MARK_SELECTED),
            new MessageButton(MessageButton::ADD_MEDICAMENT_BUTTON_TITLE, EnumState::ADD_MEDICAMENT_SELECTED),
            new MessageButton(MessageButton::CHANGE_MEDICAMENT_BUTTON_TITLE, EnumState::CHANGE_MEDICAMENT_SELECTED),
            new MessageButton(MessageButton::DELETE_MEDICAMENT_BUTTON_TITLE, EnumState::DELETE_MEDICAMENT_SELECTED),
            new MessageButton(MessageButton::DOWNLOAD_REPORT_BUTTON_TITLE, EnumState::DOWNLOAD_REPORT_SELECTED),
            new MessageButton(MessageButton::NOTIFICATIONS_BUTTON_TITLE, EnumState::NOTIFICATIONS_SELECTED),
        ];

        $this->assertCount(6, $keyboard);
        $this->assertEquals($expectedKeyboard, $keyboard);
    }
}
