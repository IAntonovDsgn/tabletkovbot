<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

interface QueueConsumerInterface
{
    public function run(): void;

    public function requestStop(): void;
}
