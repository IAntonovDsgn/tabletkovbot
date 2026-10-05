<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\RabbitMq;

use App\Application\Services\DataSender\DataSender;
use App\Application\Services\DataSender\DataTransportInterface;
use App\Application\Services\DataSender\MessageBrokerInterface;
use App\Application\Services\PdfFactory\PdfFactoryInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Infrastructure\RabbitMq\AmqpConnectionFactoryInterface;
use App\Infrastructure\RabbitMq\Deserializer;
use App\Infrastructure\RabbitMq\MessageQueueConsumer;
use App\Infrastructure\RabbitMq\Serializer;
use App\Infrastructure\TelegramDataTransport\SendMessageException;
use DateTimeImmutable;
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
    private const int MAX_ATTEMPTS = 3;

    private FakeLogger $logger;
    private Serializer $serializer;
    private MockObject $connectionFactory;
    private MockObject $channel;
    private MockObject $transport;
    private MockObject $broker;

    /** @var (callable(AMQPMessage): void)|null */
    private $capturedCallback = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new FakeLogger();
        $this->serializer = new Serializer();
        $this->connectionFactory = $this->createMock(AmqpConnectionFactoryInterface::class);
        $this->transport = $this->createMock(DataTransportInterface::class);
        $this->broker = $this->createMock(MessageBrokerInterface::class);

        $connection = $this->createMock(AMQPStreamConnection::class);
        $this->channel = $this->createMock(AMQPChannel::class);

        $this->connectionFactory->method('create')->willReturn($connection);
        $connection->method('channel')->willReturn($this->channel);
        $this->channel->method('is_open')->willReturn(true);
        $this->channel->method('basic_qos');
        $this->channel->method('exchange_declare');
        $this->channel->method('queue_declare');
        $this->channel->method('queue_bind');

        $consumeCalls = 0;
        // By reference: an arrow function would capture the counter by value and keep
        // reporting "not consuming" forever, so the polling loop would never run.
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

    private function makeConsumer(): MessageQueueConsumer
    {
        return new MessageQueueConsumer(
            new Deserializer(),
            $this->logger,
            'rabbitmq-host',
            5672,
            '/',
            'user',
            'pass',
            'outbox',
            'telegram.send-message',
            1,
            new DataSender(
                $this->transport,
                $this->createMock(PdfFactoryInterface::class),
            ),
            $this->broker,
            self::MAX_ATTEMPTS,
            $this->connectionFactory,
        );
    }

    /**
     * Emulates the library dispatching one delivery per wait() call. The last
     * invocation requests a graceful stop so the run() loop always terminates.
     *
     * @param array<int, string|RuntimeException> $bodies
     */
    private function channelWillDeliver(MessageQueueConsumer $consumer, array $bodies): void
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
    private function validPayload(int $id, int $attempts = 0, ?MessageButton $button = null): string
    {
        return $this->serializer->serializeMessage(
            Message::restoreFromPersistence(
                $id,
                42,
                $attempts,
                'hello',
                $button === null ? [] : [$button],
            )
        );
    }

/**
     * A stop requested before run() still subscribes once, but the polling loop never
     * dispatches anything.
     */
public function testRunWithStopRequestedConsumesNothing(): void
    {
        $this->channel->expects($this->once())->method('basic_consume');
        $this->channel->expects($this->never())->method('wait');
        $this->transport->expects($this->never())->method('sendMessage');

        $consumer = $this->makeConsumer();
        $consumer->requestStop();
        $consumer->run();
    }

    /**
     * @throws JsonException
     */
    public function testSuccessfulDeliveryReachesTheTransportAndAcks(): void
    {
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, [$this->validPayload(7)]);

        $this->channel->expects($this->once())->method('basic_ack')->with(11);

        $sent = null;
        $this->transport->expects($this->once())
            ->method('sendMessage')
            ->willReturnCallback(function (Message $message) use (&$sent, $consumer): void {
                $sent = $message;
                $consumer->requestStop();
            });

        $consumer->run();

        self::assertInstanceOf(Message::class, $sent);
        self::assertSame(7, $sent->getId());
        self::assertSame(42, $sent->getChatId());
        self::assertSame('hello', $sent->getText());
    }

    /**
     * @throws JsonException
     */
    public function testButtonsSurviveTheRoundTripToTheTransport(): void
    {
        $button = new MessageButton('Menu', EnumState::MENU, 'extra');
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, [$this->validPayload(7, 0, $button)]);

        $this->channel->method('basic_ack');

        $buttons = null;
        $this->transport->method('sendMessage')
            ->willReturnCallback(function (Message $message) use (&$buttons, $consumer): void {
                $buttons = $message->getButtons();
                $consumer->requestStop();
            });

        $consumer->run();

        self::assertCount(1, $buttons);
        self::assertSame('Menu', $buttons[0]->getTitle());
        self::assertSame($button->getNewState(), $buttons[0]->getNewState());
        self::assertSame(EnumState::MENU->value, $buttons[0]->getNewState());
        self::assertSame('extra', $buttons[0]->getAdditionalPayload());
    }

    public function testMalformedPayloadIsNackedWithoutDelivery(): void
    {
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, ['definitely-not-json']);

        $this->channel->expects($this->once())->method('basic_nack')->with(11, false, false);
        $this->transport->expects($this->never())->method('sendMessage');

        $consumer->run();

        self::assertTrue($this->logger->hasRecordWithContext('phase', 'deserialize'));
    }

    /**
     * A failed delivery is republished with a bumped attempt counter and the delivery acked —
     * dropping it straight away would lose the reminder.
     *
     * @throws JsonException
     */
    public function testFailedDeliveryIsRepublishedWithBumpedAttempts(): void
    {
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, [$this->validPayload(7)]);

        $this->transport->method('sendMessage')
            ->willThrowException(new SendMessageException('chat not found'));

        $republished = null;
        $this->broker->expects($this->once())
            ->method('publishMessage')
            ->willReturnCallback(function (Message $message) use (&$republished, $consumer): void {
                $republished = $message;
                $consumer->requestStop();
            });

        $this->channel->expects($this->once())->method('basic_ack')->with(11);
        $this->channel->expects($this->never())->method('basic_nack');

        $consumer->run();

        self::assertInstanceOf(Message::class, $republished);
        self::assertSame(1, $republished->getAttempts());
        self::assertTrue($this->logger->hasRecordWithContext('phase', 'delivery'));
    }

    /**
     * Once the attempts run out the message is dropped instead of looping forever.
     *
     * @throws JsonException
     */
    public function testMessageIsDroppedAfterMaxAttempts(): void
    {
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, [$this->validPayload(7, self::MAX_ATTEMPTS - 1)]);

        $this->transport->method('sendMessage')
            ->willThrowException(new SendMessageException('chat not found'));

        $this->broker->expects($this->never())->method('publishMessage');
        $this->channel->expects($this->once())->method('basic_nack')->with(11, false, false);

        $consumer->run();

        self::assertTrue($this->logger->hasRecordWithContext('phase', 'dropped'));
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
        $this->transport->method('sendMessage')
            ->willReturnCallback(function (Message $message) use (&$processedIds, $consumer): void {
                $processedIds[] = $message->getId();
                if (count($processedIds) === 3) {
                    $consumer->requestStop();
                }
            });

        $consumer->run();

        self::assertSame([1, 2, 3], $processedIds);
    }

    /**
     * A broken connection must not escape run(): that used to kill the worker and silently
     * stop message delivery.
     */
    public function testConnectionLossIsLoggedAndEndsTheLoop(): void
    {
        $consumer = $this->makeConsumer();
        $this->channelWillDeliver($consumer, [new RuntimeException('broken pipe')]);

        $this->transport->expects($this->never())->method('sendMessage');

        $consumer->run();

        self::assertTrue($this->logger->hasRecordWithContext('phase', 'connection_lost'));
    }
}
