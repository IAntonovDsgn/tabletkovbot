<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Report\Report;
use App\Domain\Entities\Session\States\EnumState;
use App\Infrastructure\RabbitMq\Deserializer;
use App\Infrastructure\RabbitMq\Serializer;
use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\TestCase;

class DeserializerTest extends TestCase
{
    private Serializer $serializer;
    private Deserializer $deserializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = new Serializer();
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
            2,
            EnumMessageText::MENU->value,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
                new MessageButton('Подтвердить', EnumState::DELETE_MEDICAMENT_CONFIRMED, '42'),
            ],
        );

        $restored = $this->deserializer->deserializeMessage($this->serializer->serializeMessage($original));

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getChatId(), $restored->getChatId());
        $this->assertSame($original->getAttempts(), $restored->getAttempts());
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
        $message = $this->deserializer->deserializeMessage(
            '{"id":1,"chat_id":2,"attempts":0,"buttons":[]}'
        );

        $this->assertSame(1, $message->getId());
        $this->assertSame(2, $message->getChatId());
        $this->assertSame('', $message->getText());
        $this->assertSame([], $message->getButtons());
    }

    /**
     * @throws JsonException
     */
    public function testRoundTripReport(): void
    {
        $original = Report::restoreFromPersistence(
            9,
            12345,
            new DateTimeImmutable('2023-01-05 00:00:00'),
            2,
        );

        $restored = $this->deserializer->deserializeReport($this->serializer->serializeReport($original));

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getChatId(), $restored->getChatId());
        $this->assertSame($original->getAttempts(), $restored->getAttempts());
        $this->assertSame('2023-01-05', $restored->getStartDate()->format(Report::DB_DATE_FORMAT));
        $this->assertTrue($restored->isExistInPersistence());
    }

    /**
     * @throws JsonException
     */
    public function testDeserializesReportWithLeapDay(): void
    {
        $report = $this->deserializer->deserializeReport(
            '{"id":1,"chat_id":2,"attempts":0,"start_date":"2024-02-29"}'
        );

        $this->assertSame('2024-02-29', $report->getStartDate()->format(Report::DB_DATE_FORMAT));
    }

    public function testRejectsReportWithUserFacingDateFormat(): void
    {
        $this->expectException(JsonException::class);

        $this->deserializer->deserializeReport('{"id":1,"chat_id":2,"attempts":0,"start_date":"05.01.2023"}');
    }

    public function testRejectsReportWithMissingStartDate(): void
    {
        $this->expectException(JsonException::class);

        $this->deserializer->deserializeReport('{"id":1,"chat_id":2,"attempts":0}');
    }

    public function testRejectsReportWithNonStringStartDate(): void
    {
        $this->expectException(JsonException::class);

        $this->deserializer->deserializeReport('{"id":1,"chat_id":2,"attempts":0,"start_date":20230105}');
    }

    public function testRejectsReportWithoutAttempts(): void
    {
        $this->expectException(JsonException::class);

        $this->deserializer->deserializeReport('{"id":1,"chat_id":2,"start_date":"2023-01-05"}');
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

        $this->deserializer->deserializeMessage('{"chat_id":2,"attempts":0,"text":"hi","buttons":[]}');
    }

    public function testRejectsMissingAttempts(): void
    {
        $this->expectException(JsonException::class);

        $this->deserializer->deserializeMessage('{"id":1,"chat_id":2,"text":"hi","buttons":[]}');
    }

    public function testRejectsUnknownState(): void
    {
        $payload = '{"id":1,"chat_id":2,"attempts":0,"text":"hi","buttons":[{"title":"T","new_state":"NOPE"}]}';

        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage($payload);
    }

    public function testRejectsNonIntId(): void
    {
        $this->expectException(JsonException::class);

        $this->deserializer->deserializeMessage('{"id":"7","chat_id":2,"attempts":0,"text":"","buttons":[]}');
    }

    public function testRejectsNonStringText(): void
    {
        $this->expectException(JsonException::class);

        $this->deserializer->deserializeMessage('{"id":1,"chat_id":2,"attempts":0,"text":42,"buttons":[]}');
    }

    public function testRejectsNonArrayButtons(): void
    {
        $this->expectException(JsonException::class);

        $this->deserializer->deserializeMessage('{"id":1,"chat_id":2,"attempts":0,"text":"","buttons":"nope"}');
    }

    public function testRejectsNumericAdditionalPayload(): void
    {
        $payload = '{"id":1,"chat_id":2,"attempts":0,"text":"","buttons":['
            . '{"title":"T","new_state":"menu","additional_payload":42}]}';

        $this->expectException(JsonException::class);
        $this->deserializer->deserializeMessage($payload);
    }
}
