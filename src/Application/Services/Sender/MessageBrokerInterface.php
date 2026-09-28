<?php

declare(strict_types=1);

namespace App\Application\Services\Sender;

use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;

interface MessageBrokerInterface
{
    public function publishMessage(Message $message): void;

    public function publishReport(Report $report): void;

    public function close(): void;
}
