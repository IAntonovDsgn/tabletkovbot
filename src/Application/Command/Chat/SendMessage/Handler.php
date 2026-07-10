<?php

namespace App\Application\Command\Chat\SendMessage;

use App\Domain\Chat\ChatRepositoryInterface;
use App\Domain\TelegramMessage\TelegramMessageFacadeInterface;
use SendMessageDTO;

final readonly class Handler
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
        private TelegramMessageFacadeInterface $telegramFacadeInterface,
    ) {}

    public function __invoke(SendMessageDTO $sendMessageDTO): void
    {

    }
}
