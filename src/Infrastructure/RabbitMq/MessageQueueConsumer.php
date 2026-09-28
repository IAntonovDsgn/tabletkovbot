<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Application\Services\MessageSender\MessageSender;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

final class MessageQueueConsumer
{
    private bool $stopRequested = false;

    public function __construct(
        private readonly Deserializer $deserializer,
        private readonly LoggerInterface $logger,
        private readonly string $host,
        private readonly int $port,
        private readonly string $vhost,
        private readonly string $user,
        private readonly string $password,
        private readonly string $exchange,
        private readonly string $queueName,
        private readonly int $pollInterval,
        private readonly MessageSender $messageSenderService,
        private readonly AmqpConnectionFactoryInterface $connectionFactory,
    ) {}

    /**
     * @throws AMQPException
     * @throws Exception
     */
    public function run(): void
    {
        $connection = $this->connectionFactory->create(
            $this->host,
            $this->port,
            $this->vhost,
            $this->user,
            $this->password,
        );
        $channel = $connection->channel();
        $this->declareTopology($channel);
        $messageProcessingCallback = function (AMQPMessage $message): void {
            try {
                $domainMessage = $this->deserializer->deserializeMessage($message->getBody());
                $this->messageSenderService->sendMessage($domainMessage);
                $message->ack();
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage());
                $message->nack(true);
            }
        };

        $channel->basic_qos(0, 1, null);
        $channel->basic_consume(
            $this->queueName,
            '',
            false,
            false,
            false,
            false,
            $messageProcessingCallback
        );

        while ($this->stopRequested === false && $channel->is_consuming()) {
            try {
                $channel->wait(null, false, $this->pollInterval);
            } catch (AMQPTimeoutException) {
                continue;
            }
        }

        $channel->close();
        $connection->close();
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }

    private function declareTopology(AMQPChannel $channel): void
    {
        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $channel->queue_declare($this->queueName, false, true, false, false);
        $channel->queue_bind($this->queueName, $this->exchange, $this->queueName);
    }
}
