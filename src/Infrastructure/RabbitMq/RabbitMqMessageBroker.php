<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Application\Message\Services\MessageBrokerInterface;
use App\Domain\Entities\Message\Message;
use App\Infrastructure\Exceptions\AMQPException;
use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

final class RabbitMqMessageBroker implements MessageBrokerInterface
{
    private const string CONTENT_TYPE_JSON = 'application/json';

    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly MessagePayloadSerializer $serializer,
        private readonly LoggerInterface $logger,
        private readonly string $host,
        private readonly int $port,
        private readonly string $vhost,
        private readonly string $user,
        private readonly string $password,
        private readonly string $exchange,
        private readonly string $queue,
        private readonly float $confirmTimeoutSeconds = 5.0,
        private readonly AmqpConnectionFactoryInterface $connectionFactory = new AmqpConnectionFactory(),
    ) {}

    /**
     * @throws JsonException
     * @throws AMQPException
     */
    public function publish(Message $message): void
    {
        $channel = $this->connect();
        $channel->basic_publish(
            new AMQPMessage($this->serializer->serialize($message), [
                'content_type' => self::CONTENT_TYPE_JSON,
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]),
            $this->exchange,
            $this->queue,
        );

        $channel->wait_for_pending_acks($this->confirmTimeoutSeconds);
    }

    public function close(): void
    {
        try {
            if ($this->channel instanceof AMQPChannel && $this->channel->is_open()) {
                $this->channel->close();
            }
        } catch (Throwable $e) {
            $this->logger->warning($e, ['phase' => 'close_channel']);
        }

        try {
            if ($this->connection instanceof AMQPStreamConnection && $this->connection->isConnected()) {
                $this->connection->close();
            }
        } catch (Throwable $e) {
            $this->logger->warning($e, ['phase' => 'close_connection']);
        }

        $this->channel = null;
        $this->connection = null;
    }

    /**
     * @throws AMQPException
     */
    private function connect(): AMQPChannel
    {
        if ($this->channel instanceof AMQPChannel && $this->channel->is_open()) {
            return $this->channel;
        }

        $this->close();

        $this->connection = $this->connectionFactory->create(
            $this->host,
            $this->port,
            $this->vhost,
            $this->user,
            $this->password,
        );

        $channel = $this->connection->channel();
        $this->channel = $channel;

        $this->declareTopology($channel);
        $channel->confirm_select();

        return $channel;
    }

    private function declareTopology(AMQPChannel $channel): void
    {
        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $channel->queue_declare($this->queue, false, true, false, false);
        $channel->queue_bind($this->queue, $this->exchange, $this->queue);
    }
}
