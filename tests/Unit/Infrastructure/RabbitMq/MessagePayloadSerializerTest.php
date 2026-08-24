<?php

namespace Tests\Unit\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use App\Infrastructure\RabbitMq\MessagePayloadSerializer;
use PHPUnit\Framework\TestCase;

class MessagePayloadSerializerTest extends TestCase
{
    private MessagePayloadSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = new MessagePayloadSerializer();
    }

    public function testSerializesMessageWithButtons(): void
    {
        $message = Message::restoreFromPersistence(
            7,
            12345,
            EnumMessageText::MENU->value,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
                new MessageButton('Подтвердить', EnumState::DELETE_MEDICAMENT_CONFIRMED, '42'),
            ],
        );

        $payload = $this->serializer->serialize($message);
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(7, $decoded['id']);
        $this->assertSame(12345, $decoded['chat_id']);
        $this->assertSame(EnumMessageText::MENU->value, $decoded['text']);

        $expectedButtons = [
            ['title' => 'Меню', 'new_state' => 'menu', 'additional_payload' => null],
            ['title' => 'Подтвердить', 'new_state' => 'delete_medicament_confirmed', 'additional_payload' => '42'],
        ];
        $this->assertSame($expectedButtons, $decoded['buttons']);
    }

    public function testSerializesMessageWithoutTextAndButtons(): void
    {
        $message = Message::restoreFromPersistence(1, 1);

        $payload = $this->serializer->serialize($message);
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('', $decoded['text']);
        $this->assertSame([], $decoded['buttons']);
    }

    public function testPayloadIsUtf8Json(): void
    {
        $message = Message::restoreFromPersistence(1, 1, 'Привет');
        $payload = $this->serializer->serialize($message);

        $this->assertStringContainsString('Привет', $payload);
        $this->assertSame('{"id":1,"chat_id":1,"text":"Привет","buttons":[]}', $payload);
    }
}
