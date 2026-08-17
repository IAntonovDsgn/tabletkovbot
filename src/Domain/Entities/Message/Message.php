<?php

declare(strict_types=1);

namespace App\Domain\Entities\Message;

final readonly class Message
{
    /**
     * @param MessageButton[] $buttons
     */
    public function __construct(
        private int $chatId,
        private ?string $text,
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
        return $this->text ?? '';
    }

    /**
     * @return MessageButton[]
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}