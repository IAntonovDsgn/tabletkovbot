<?php

declare(strict_types=1);

namespace App\Application\MessageService;

use App\Domain\Entities\Message\Message;

interface MessageBrokerInterface
{
    public function publish(Message $message): void;

    public function close(): void;
}
