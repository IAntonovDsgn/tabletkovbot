<?php

namespace Tests\Unit\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Infrastructure\RabbitMq\Deserializer;
use App\Infrastructure\RabbitMq\MessagePayloadSerializer;
use JsonException;
use PHPUnit\Framework\TestCase;

class DeserializerTest extends TestCase
{
    private MessagePayloadSerializer $serializer;
    private Deserializer $deserializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = new MessagePayloadSerializer();
        $this->deserializer = new Deserializer();
    }

    /**
     * @throws JsonException
     */
    public function testRoundTripWithButtons(): void
    {
        $original = Message::restoreFromPersistence(
            7,
            12345,
            EnumMessageText::MENU->value,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
                new MessageButton('Подтвердить', EnumState::DELETE_MEDICAMENT_CONFIRMED, '42'),
            ],
        );

        $restored = $this->deserializer->deserializeMessage($this->serializer->serialize($original));

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getChatId(), $restored->getChatId());
        $this->assertSame($original->getText(), $restored->getText());

        $originalButtons = $original->getButtons();
        $restoredButtons = $restored->getButtons();
        $this->assertCount(count($originalButtons), $restoredButtons);
        foreach ($originalButtons as $index => $button) {
            $this->assertInstanceOf(MessageButton::class, $restoredButtons[$index]);
            $this->assertSame($button->getTitle(), $restoredButtons[$index]->getTitle());
            $this->assertSame($button->getNewState(), $restoredButtons[$index]->getNewState());
            $this->assertSame($button->getAdditionalPayload(), $restoredButtons[$index]->getAdditionalPayload());
        }
    }

    /**
     * @throws JsonException
     */
    public function testDeserializesPayloadWithoutTextAndButtons(): void
    {
        $message = $this->deserializer->deserializeMessage('{"id":1,"chat_id":2,"text":"","buttons":[]}');

        $this->assertSame(1, $message->getId());
        $this->assertSame(2, $message->getChatId());
        $this->assertSame('', $message->getText());
        $this->assertSame([], $message->getButtons());
    }

    public function testRejectsMalformedJson(): void
    {
        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage('not-json');
    }

    public function testRejectsNonObjectJson(): void
    {
        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage('"string"');
    }

    public function testRejectsMissingId(): void
    {
        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage('{"chat_id":2,"text":"hi","buttons":[]}');
    }

    public function testRejectsUnknownState(): void
    {
        $payload = '{"id":1,"chat_id":2,"text":"hi","buttons":[{"title":"T","new_state":"NOPE"}]}';

        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage($payload);
    }

    public function testRejectsNonIntId(): void
    {
        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage('{"id":"7","chat_id":2,"text":"","buttons":[]}');
    }

    public function testRejectsNonStringText(): void
    {
        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage('{"id":1,"chat_id":2,"text":42,"buttons":[]}');
    }

    public function testRejectsNonArrayButtons(): void
    {
        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage('{"id":1,"chat_id":2,"text":"","buttons":"nope"}');
    }

    public function testRejectsNumericAdditionalPayload(): void
    {
        $payload = '{"id":1,"chat_id":2,"text":"","buttons":[{"title":"T","new_state":"menu","additional_payload":42}]}';

        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage($payload);
    }
}
