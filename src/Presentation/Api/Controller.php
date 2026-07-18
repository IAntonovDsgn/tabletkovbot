<?php

namespace App\Presentation\Api;

use App\Application\BotManager\BotManager;
use App\Domain\Entities\Message\MessageServiceInterface;
use App\Domain\Entities\Message\MessageInputDTO;

final readonly class Controller
{
    public function __construct(
        private BotManager $botManager,
        private MessageServiceInterface $messageService,
    )
    {}

    public function process(MessageInputDTO $request): void
    {
        $responseParams = $this->botManager->process($request);
        $this->messageService->sendMessage($responseParams->chatId, $responseParams->message, $responseParams->buttons);
    }
}
