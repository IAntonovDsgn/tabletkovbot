<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Infrastructure\Exceptions\AMQPException;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final readonly class AmqpConnectionFactory implements AmqpConnectionFactoryInterface
{
    public function create(
        string $host,
        int $port,
        string $vhost,
        string $user,
        string $password,
    ): AMQPStreamConnection {
        try {
            return new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        } catch (\Exception $e) {
            throw new AMQPException($e->getMessage());
        }
    }
}
