<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;

final readonly class StateChangeNotificationTimeSelectedHandler implements StateHandlerInterface
{
    public function __construct(
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    public function handle(RequestDTO $params): void {
        $this->outboxRepository->insert(
            Message::create(
                $params->chatId,
                EnumMessageText::ENTER_TIME->value,
                [
                    new MessageButton(MessageButton::MENU, EnumState::MENU),
                ]
            )
        );
    }
}
