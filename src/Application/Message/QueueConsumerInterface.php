<?php

declare(strict_types=1);

namespace App\Application\Message;

use App\Domain\Entities\Message\Message;
use Closure;

interface QueueConsumerInterface
{
    /**
     * @param Closure(Message $message): void $onMessage
     */
    public function run(Closure $onMessage): void;

    public function requestStop(): void;

    public function close(): void;
}
