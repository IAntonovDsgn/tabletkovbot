<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;

final readonly class StateMenuHandler implements StateHandlerInterface
{
    public function __construct(
        private KeyboardFactory $keyboardFactory,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    public function handle(RequestDTO $params): void {
        $this->outboxRepository->insert(
            Message::create(
                $params->chatId,
                EnumMessageText::MENU->value,
                $this->keyboardFactory->makeMenuKeyboard()
            )
        );
    }
}
