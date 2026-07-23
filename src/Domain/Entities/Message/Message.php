<?php

namespace App\Domain\Entities\Message;

use App\Domain\Entities\Message\Button\Button;

final readonly class Message
{
    /**
     * @param Button[] $buttons
     */
    public function __construct(
        private int $chatId,
        private ?EnumMessageText $text,
        private array $buttons,
    ) {
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getText(): string
    {
        return $this->text->value;
    }

    /**
     * @return Button[]
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }
}
