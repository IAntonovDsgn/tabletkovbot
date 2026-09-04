<?php

namespace Tests\Unit\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\Message;
use App\Infrastructure\RabbitMq\AmqpConnectionFactoryInterface;
use App\Infrastructure\RabbitMq\MessagePayloadDeserializer;
use App\Infrastructure\RabbitMq\MessagePayloadSerializer;
use App\Infrastructure\RabbitMq\RabbitMqQueueConsumer;
use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\FakeLogger;

class RabbitMqQueueConsumerTest extends TestCase
{
    private FakeLogger $logger;
    private MockObject $connectionFactory;
    private MockObject $channel;

    /** @var callable(AMQPMessage): void|null */
    private $capturedCallback = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new FakeLogger();
        $this->connectionFactory = $this->createMock(AmqpConnectionFactoryInterface::class);
        $connection = $this->createMock(AMQPStreamConnection::class);
        $this->channel = $this->createMock(AMQPChannel::class);

        $this->connectionFactory->method('create')
            ->willReturn($connection);
        $connection->method('channel')
            ->willReturn($this->channel);
        $this->channel->method('is_open')->willReturn(true);
        $this->channel->method('basic_qos');

        $consumeCalls = 0;
        $this->channel->method('is_consuming')
            ->willReturnCallback(function () use (&$consumeCalls): bool {
                return $consumeCalls > 0;
            });

        $this->channel->method('basic_consume')
            ->willReturnCallback(function (...$args) use (&$consumeCalls): string {
                $consumeCalls++;
                $this->capturedCallback = $args[6];
                self::assertSame('telegram.send-message', $args[0]);
                self::assertFalse($args[3], 'manual acks are required');

                return 'consumer-tag';
            });
    }

    private function makeConsumer(): RabbitMqQueueConsumer
    {
        return new RabbitMqQueueConsumer(
            new MessagePayloadDeserializer(),
            $this->logger,
            'rabbitmq-host',
            5672,
            '/',
            'user',
            'pass',
            'outbox',
            'telegram.send-message',
            1,
            $this->connectionFactory,
        );
    }

    /**
     * Emulates the library dispatching one delivery per wait() call. The last
     * invocation requests a graceful stop so the run() loop always terminates.
     */
    private function channelWillDeliver(RabbitMqQueueConsumer $consumer, array $bodies): void
    {
        $invocation = 0;
        $this->channel->method('wait')
            ->willReturnCallback(function () use (&$invocation, $bodies, $consumer): AMQPMessage|false {
                if (!isset($bodies[$invocation])) {
                    $consumer->requestStop();

                    return false;
                }

                $body = $bodies[$invocation];
                $invocation++;

                if ($body instanceof RuntimeException) {
                    throw $body;
                }

                $deliveryTag = 10 + $invocation;
                $message = new AMQPMessage($body);
                $message->setChannel($this->channel);
                $message->setDeliveryInfo($deliveryTag, false, 'outbox', 'telegram.send-message');

                ($this->capturedCallback)($message);

                return $message;
            });
    }

    /**
     * @throws JsonException
     */
    private function validPayload(int $id): string
    {
        return new MessagePayloadSerializer()
            ->serialize(Message::restoreFromPersistence($id, 42, 'hello'));
    }

    public function testRunWithStopRequestedNeverConnects(): void
    {
        $this->connectionFactory->expects($this->never())->method('create');

        $consumer = $this->makeConsumer();
        $consumer->requestStop();
        $consumer->run(function (): void {
            self::fail('Callback must not be invoked.');
        });

        self::assertTrue($this->logger->hasMessage('Queue consumer started'));
        self::assertTrue($this->logger->hasMessage('Queue consumer stopped gracefully'));
    }

    /**
     * @throws JsonException
     */
    public function testSuccessfulDeliveryPassesMessageToCallbackAndAcks(): void
    {
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, [$this->validPayload(7)]);

        $this->channel->expects($this->once())->method('basic_ack')->with(11);

        $received = null;
        $consumer->run(function (Message $message) use (&$received, $consumer): void {
            $received = $message;
            $consumer->requestStop();
        });

        self::assertInstanceOf(Message::class, $received);
        self::assertSame(7, $received->getId());
        self::assertSame(42, $received->getChatId());
        self::assertSame('hello', $received->getText());
    }

    public function testMalformedPayloadIsNackedWithoutCallingCallback(): void
    {
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, ['definitely-not-json']);

        $this->channel->expects($this->once())->method('basic_nack')->with(11, false, false);

        $consumer->run(function (): void {
            self::fail('Callback must not be invoked for malformed payloads.');
        });

        self::assertTrue($this->logger->hasRecordWithContext('phase', 'deserialize'));
    }

    /**
     * @throws JsonException
     */
    public function testFailingCallbackIsLoggedAndMessageIsDropped(): void
    {
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, [$this->validPayload(7)]);

        $this->channel->expects($this->once())->method('basic_nack')->with(11, false, false);

        $consumer->run(function (): void {
            throw new RuntimeException('Bad Request: chat not found');
        });

        $dropRecords = [];
        foreach ($this->logger->records as $record) {
            if (isset($record['context']['phase']) && $record['context']['phase'] === 'delivery') {
                $dropRecords[] = $record;
            }
        }

        self::assertCount(1, $dropRecords);
        self::assertSame(7, $dropRecords[0]['context']['id']);
        self::assertSame(42, $dropRecords[0]['context']['chat_id']);
    }

    /**
     * @throws JsonException
     */
    public function testBacklogIsDrainedWithoutExtraSubscriptions(): void
    {
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, [
            $this->validPayload(1),
            $this->validPayload(2),
            $this->validPayload(3),
        ]);

        $this->channel->expects($this->once())->method('basic_consume');
        $this->channel->expects($this->exactly(3))->method('basic_ack');

        $processedIds = [];
        $consumer->run(function (Message $message) use (&$processedIds, $consumer): void {
            $processedIds[] = $message->getId();
            if (count($processedIds) === 3) {
                $consumer->requestStop();
            }
        });

        self::assertSame([1, 2, 3], $processedIds);
    }

    public function testConnectionLossLogsWarningAndReconnects(): void
    {
        $this->connectionFactory->expects($this->exactly(2))->method('create');

        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, [new RuntimeException('broken pipe')]);

        $consumer->run(function (): void {
            self::fail('Callback must not be invoked when the connection dies.');
        });

        self::assertTrue($this->logger->hasRecordWithContext('phase', 'connection_lost'));
        self::assertTrue($this->logger->hasMessage('Queue consumer stopped gracefully'));
    }
}
