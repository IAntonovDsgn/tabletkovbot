<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Infrastructure\Exceptions\AMQPException;
use PhpAmqpLib\Connection\AMQPStreamConnection;

interface AmqpConnectionFactoryInterface
{
    /**
     * @throws AMQPException
     */
    public function create(
        string $host,
        int $port,
        string $vhost,
        string $user,
        string $password,
    ): AMQPStreamConnection;
}
