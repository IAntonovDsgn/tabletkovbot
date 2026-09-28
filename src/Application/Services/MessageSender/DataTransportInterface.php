<?php

declare(strict_types=1);

namespace App\Application\Services\MessageSender;

use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Message\Message;

interface DataTransportInterface
{
    public function sendMessage(Message $message): void;

    /**
     * @return RequestDTO[]
     */
    public function getUpdates(): array;
}
