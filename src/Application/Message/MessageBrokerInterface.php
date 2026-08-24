<?php

declare(strict_types=1);

namespace App\Application\Message;

use App\Domain\Entities\Message\Message;
use App\Domain\Exceptions\Interior\AMQPException;

interface MessageBrokerInterface
{
    /**
     * @throws AMQPException
     */
    public function publish(Message $message): void;

    public function close(): void;
}
