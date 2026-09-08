<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\Message;
use App\Infrastructure\Exceptions\AMQPException;
use Closure;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

final class RabbitMqQueueConsumer implements QueueConsumerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;
    private bool $stopRequested = false;

    public function __construct(
        private readonly MessagePayloadDeserializer $deserializer,
        private readonly LoggerInterface $logger,
        private readonly string $host,
        private readonly int $port,
        private readonly string $vhost,
        private readonly string $user,
        private readonly string $password,
        private readonly string $exchange,
        private readonly string $queue,
        private readonly int $pollIntervalMs = 1000,
        private readonly AmqpConnectionFactoryInterface $connectionFactory = new AmqpConnectionFactory(),
    ) {}

    public function run(Closure $onMessage): void
    {
        $this->logger->info('Queue consumer started', ['queue' => $this->queue]);

        while (!$this->stopRequested) {
            try {
                $channel = $this->connect();

                if (!$channel->is_consuming()) {
                    $this->subscribe($channel, $onMessage);
                }

                $hasDelivery = $channel->wait(null, true) !== false;
                if (!$hasDelivery && !$this->stopRequested) {
                    usleep($this->pollIntervalMs * 1000);
                }
            } catch (Throwable $e) {
                $this->logger->warning($e, ['phase' => 'connection_lost']);
                $this->close();
                usleep($this->pollIntervalMs * 1000);
            }
        }

        $this->close();
        $this->logger->info('Queue consumer stopped gracefully');
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
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
     * @param Closure(Message $message): void $onMessage
     * @throws Throwable
     */
    private function subscribe(AMQPChannel $channel, Closure $onMessage): void
    {
        $channel->basic_qos(0, 1, false);
        $channel->basic_consume(
            $this->queue,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $amqpMessage) use ($onMessage): void {
                $this->handleDelivery($amqpMessage, $onMessage);
            },
        );
    }

    private function handleDelivery(AMQPMessage $amqpMessage, Closure $onMessage): void
    {
        try {
            $message = $this->deserializer->deserialize($amqpMessage->getBody());
        } catch (Throwable $e) {
            $this->logger->error($e, [
                'payload' => substr($amqpMessage->getBody(), 0, 500),
                'phase' => 'deserialize',
            ]);
            $amqpMessage->nack();
            return;
        }

        try {
            $onMessage($message);
            $amqpMessage->ack();
        } catch (Throwable $e) {
            $this->logger->error($e, [
                'id' => $message->getId(),
                'chat_id' => $message->getChatId(),
                'phase' => 'delivery',
            ]);
            $amqpMessage->nack();
        }
    }

    /**
     * @throws AMQPException
     */
    private function connect(): AMQPChannel
    {
        if ($this->channel instanceof AMQPChannel && $this->channel->is_open() && $this->channel->is_consuming()) {
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

        return $channel;
    }

    private function declareTopology(AMQPChannel $channel): void
    {
        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $channel->queue_declare($this->queue, false, true, false, false);
        $channel->queue_bind($this->queue, $this->exchange, $this->queue);
    }
}
