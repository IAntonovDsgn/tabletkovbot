<?php

namespace App\Domain\Entities\Message;

final readonly class Message
{
    /**
     * @param EnumMessageButton[] $buttons
     */
    public function __construct(
        private int $chatId,
        private ?EnumOutgoingText $text,
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
     * @return EnumMessageButton[]
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }
}
