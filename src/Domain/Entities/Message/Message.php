<?php

namespace App\Domain\Entities\Message;

final readonly class Message
{
    /**
     * @param MessageButton[] $buttons
     */
    public function __construct(
        private int $chatId,
        private ?EnumMessageText $text,
        private array $buttons,
        private ?int $id = null,
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
     * @return MessageButton[]
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }
}
