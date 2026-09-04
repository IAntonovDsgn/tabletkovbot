<?php

namespace Tests\Unit\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Infrastructure\Exceptions\AMQPException;
use App\Infrastructure\RabbitMq\AmqpConnectionFactoryInterface;
use App\Infrastructure\RabbitMq\MessagePayloadSerializer;
use App\Infrastructure\RabbitMq\RabbitMqMessageBroker;
use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RabbitMqMessageBrokerTest extends TestCase
{
    private MessagePayloadSerializer $serializer;
    private MockObject $logger;
    private MockObject $connectionFactory;
    private MockObject $connection;
    private MockObject $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = new MessagePayloadSerializer();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->connectionFactory = $this->createMock(AmqpConnectionFactoryInterface::class);
        $this->connection = $this->createMock(AMQPStreamConnection::class);
        $this->channel = $this->createMock(AMQPChannel::class);

        $this->connectionFactory->method('create')
            ->willReturn($this->connection);
        $this->connection->method('channel')
            ->willReturn($this->channel);
    }

    private function makeBroker(): RabbitMqMessageBroker
    {
        return new RabbitMqMessageBroker(
            $this->serializer,
            $this->logger,
            'rabbitmq-host',
            5672,
            '/',
            'user',
            'pass',
            'outbox',
            'telegram.send-message',
            5.0,
            $this->connectionFactory,
        );
    }

    private function makeMessage(): Message
    {
        return Message::restoreFromPersistence(7, 42, EnumMessageText::MENU->value);
    }

    /**
     * @throws AMQPException
     * @throws JsonException
     */
    public function testFirstPublishDeclaresTopologyConfirmsAndPublishesPersistentMessage(): void
    {
        $this->channel->expects($this->once())
            ->method('exchange_declare')
            ->with('outbox', 'direct', false, true, false);
        $this->channel->expects($this->once())
            ->method('queue_declare')
            ->with('telegram.send-message', false, true, false, false);
        $this->channel->expects($this->once())
            ->method('queue_bind')
            ->with('telegram.send-message', 'outbox', 'telegram.send-message');
        $this->channel->expects($this->once())->method('confirm_select');
        $this->channel->expects($this->once())
            ->method('wait_for_pending_acks')
            ->with(5.0);

        $publishedMessage = null;
        $this->channel->expects($this->once())
            ->method('basic_publish')
            ->willReturnCallback(function (AMQPMessage $message) use (&$publishedMessage): void {
                $publishedMessage = $message;
            });

        $this->makeBroker()->publish($this->makeMessage());

        self::assertInstanceOf(AMQPMessage::class, $publishedMessage);
        self::assertSame(
            $this->serializer->serialize($this->makeMessage()),
            $publishedMessage->getBody(),
        );
        self::assertSame('application/json', $publishedMessage->get('content_type'));
        self::assertSame(AMQPMessage::DELIVERY_MODE_PERSISTENT, $publishedMessage->get('delivery_mode'));
    }

    /**
     * @throws AMQPException
     * @throws JsonException
     */
    public function testSecondPublishReusesChannelWithoutRedeclaringTopology(): void
    {
        $this->channel->method('is_open')->willReturn(true);

        $this->channel->expects($this->once())->method('exchange_declare');
        $this->channel->expects($this->once())->method('queue_declare');
        $this->channel->expects($this->once())->method('queue_bind');
        $this->channel->expects($this->once())->method('confirm_select');

        $this->connectionFactory->expects($this->once())->method('create');

        $this->channel->expects($this->exactly(2))->method('basic_publish');
        $this->channel->method('wait_for_pending_acks');

        $broker = $this->makeBroker();
        $broker->publish($this->makeMessage());
        $broker->publish($this->makeMessage());
    }

    /**
     * @throws AMQPException
     * @throws JsonException
     */
    public function testPublishFailsWhenBrokerDoesNotConfirm(): void
    {
        $this->channel->method('is_open')->willReturn(true);
        $this->channel->method('wait_for_pending_acks')
            ->willThrowException(new AMQPTimeoutException('no confirm within timeout'));

        $this->expectException(AMQPTimeoutException::class);

        $this->makeBroker()->publish($this->makeMessage());
    }

    /**
     * @throws AMQPException
     * @throws JsonException
     */
    public function testDeadChannelTriggersNewConnectionOnNextPublish(): void
    {
        $isOpen = true;
        $this->channel->method('is_open')
            ->willReturnCallback(function () use (&$isOpen): bool {
                return $isOpen;
            });

        $this->connectionFactory->expects($this->exactly(2))->method('create');

        $broker = $this->makeBroker();
        $broker->publish($this->makeMessage());

        $isOpen = false;
        $broker->publish($this->makeMessage());
    }

    /**
     * @throws JsonException
     */
    public function testFactoryFailureIsWrappedIntoAmqpException(): void
    {
        $this->connectionFactory->method('create')
            ->willThrowException(new AMQPException('connection refused'));

        $this->expectException(AMQPException::class);
        $this->expectExceptionMessage('connection refused');

        $this->makeBroker()->publish($this->makeMessage());
    }

    /**
     * @throws AMQPException
     * @throws JsonException
     */
    public function testCloseClosesChannelAndConnectionAndIsIdempotent(): void
    {
        $closeChannelCalls = 0;
        $this->channel->method('is_open')->willReturn(true);
        $this->channel->method('close')
            ->willReturnCallback(function () use (&$closeChannelCalls): void {
                $closeChannelCalls++;
            });

        $closeConnectionCalls = 0;
        $this->connection->method('isConnected')->willReturn(true);
        $this->connection->method('close')
            ->willReturnCallback(function () use (&$closeConnectionCalls): void {
                $closeConnectionCalls++;
            });

        $broker = $this->makeBroker();
        $broker->publish($this->makeMessage());

        $broker->close();
        $broker->close();

        self::assertSame(1, $closeChannelCalls);
        self::assertSame(1, $closeConnectionCalls);
    }

    public function testCloseWithoutConnectionIsANoop(): void
    {
        $this->makeBroker()->close();
        $this->addToAssertionCount(1);
    }
}
