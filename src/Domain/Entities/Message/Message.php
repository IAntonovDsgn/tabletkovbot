<?php

declare(strict_types=1);

namespace App\Domain\Entities\Message;

final readonly class Message
{
    /**
     * @param MessageButton[] $buttons
     */
    private function __construct(
        private ?int $id,
        private bool $isExistInPersistence,
        private int $chatId,
        private ?string $text,
        private array $buttons,
    ) {
    }

    /**
     * @param MessageButton[] $buttons
     */
    public static function create(
        int $chatId,
        ?string $text = null,
        array $buttons = [],
    ): Message {
        return new self(
            null,
            false,
            $chatId,
            $text,
            $buttons
        );
    }

    /**
     * @param MessageButton[] $buttons
     */
    public static function restoreFromPersistence(
        int $id,
        int $chatId,
        ?string $text = null,
        array $buttons = [],
    ): Message {
        return new self(
            $id,
            true,
            $chatId,
            $text,
            $buttons
        );
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

    public function isExistInPersistence(): bool
    {
        return $this->isExistInPersistence;
    }
}
