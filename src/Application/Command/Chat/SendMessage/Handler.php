<?php

namespace App\Application\Command\Chat\SendMessage;

use App\Domain\Chat\ChatRepositoryInterface;
use App\Domain\Telegram\TelegramFacadeInterface;
use SendMessageDTO;

final readonly class Handler
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
        private TelegramFacadeInterface $telegramFacadeInterface,
    ) {}

    public function __invoke(SendMessageDTO $sendMessageDTO): void
    {

    }
}
