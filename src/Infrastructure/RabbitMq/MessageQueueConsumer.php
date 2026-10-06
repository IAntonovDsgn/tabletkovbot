<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Application\Services\DataSender\DataSender;
use App\Application\Services\DataSender\MessageBrokerInterface;
use App\Domain\Entities\Message\Message;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

final class MessageQueueConsumer implements QueueConsumerInterface
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
        private readonly DataSender $dataSender,
        private readonly MessageBrokerInterface $broker,
        private readonly int $maxAttempts,
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
            } catch (Throwable $e) {
                $this->logger->error($e, ['phase' => 'deserialize', 'queue' => $this->queueName]);
                $message->nack();

                return;
            }

            try {
                $this->dataSender->sendMessage($domainMessage);
                $message->ack();
            } catch (Throwable $e) {
                $this->logger->error($e, [
                    'phase' => 'delivery',
                    'id' => $domainMessage->getId(),
                    'chat_id' => $domainMessage->getChatId(),
                    'attempts' => $domainMessage->getAttempts(),
                ]);
                $this->retryOrDrop($domainMessage, $message, $e);
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
            } catch (Throwable $e) {
                $this->logger->error($e, ['phase' => 'connection_lost', 'queue' => $this->queueName]);
                break;
            }
        }

        $this->closeConnection($channel, $connection);
    }

    private function closeConnection(AMQPChannel $channel, AMQPStreamConnection $connection): void
    {
        try {
            $channel->close();
            $connection->close();
        } catch (Throwable $e) {
            $this->logger->warning($e, ['phase' => 'close_connection', 'queue' => $this->queueName]);
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }

    private function retryOrDrop(Message $message, AMQPMessage $delivery, Throwable $cause): void
    {
        $attempts = $message->getAttempts() + 1;
        $message->setAttempts($attempts);

        if ($attempts < $this->maxAttempts) {
            try {
                $this->broker->publishMessage($message);
                $delivery->ack();

                return;
            } catch (Throwable $e) {
                $this->logger->error($e, [
                    'phase' => 'republish',
                    'id' => $message->getId(),
                    'chat_id' => $message->getChatId(),
                    'attempts' => $attempts,
                    'cause' => $cause->getMessage(),
                ]);
                $delivery->nack();

                return;
            }
        }

        $this->logger->error($cause, [
            'phase' => 'dropped',
            'id' => $message->getId(),
            'chat_id' => $message->getChatId(),
            'attempts' => $attempts,
        ]);

        $delivery->nack();
    }

    private function declareTopology(AMQPChannel $channel): void
    {
        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $channel->queue_declare($this->queueName, false, true, false, false);
        $channel->queue_bind($this->queueName, $this->exchange, $this->queueName);
    }
}
