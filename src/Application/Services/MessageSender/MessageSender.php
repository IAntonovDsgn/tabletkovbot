<?php

declare(strict_types=1);

namespace App\Application\Services\MessageSender;

use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;

final readonly class MessageSender
{
    public function __construct(
        private DataTransportInterface $dataTransport,
    ) {}

    public function sendMessage(Message $message): void
    {
        $this->dataTransport->sendMessage($message);
    }

    public function sendReport(Report $report): void
    {

    }
}
