<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Report\Report;
use App\Domain\Entities\Session\States\EnumState;
use App\Infrastructure\RabbitMq\Serializer;
use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = new Serializer();
    }

    /**
     * @throws JsonException
     */
    public function testSerializesMessageWithButtons(): void
    {
        $message = Message::restoreFromPersistence(
            7,
            12345,
            2,
            EnumMessageText::MENU->value,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
                new MessageButton('Подтвердить', EnumState::DELETE_MEDICAMENT_CONFIRMED, '42'),
            ],
        );

        $decoded = $this->decode($this->serializer->serializeMessage($message));

        $this->assertSame(7, $decoded['id']);
        $this->assertSame(12345, $decoded['chat_id']);
        $this->assertSame(2, $decoded['attempts']);
        $this->assertSame(EnumMessageText::MENU->value, $decoded['text']);

        $this->assertSame(
            [
                ['title' => 'Меню', 'new_state' => 'menu', 'additional_payload' => null],
                [
                    'title' => 'Подтвердить',
                    'new_state' => 'delete_medicament_confirmed',
                    'additional_payload' => '42',
                ],
            ],
            $decoded['buttons'],
        );
    }

    /**
     * @throws JsonException
     */
    public function testSerializesMessageWithoutTextAndButtons(): void
    {
        $message = Message::restoreFromPersistence(1, 1, 0);

        $decoded = $this->decode($this->serializer->serializeMessage($message));

        $this->assertSame('', $decoded['text']);
        $this->assertSame([], $decoded['buttons']);
    }

    /**
     * @throws JsonException
     */
    public function testMessagePayloadIsUnescapedUtf8JsonWithAttempts(): void
    {
        $message = Message::restoreFromPersistence(1, 1, 3, 'Привет');

        $payload = $this->serializer->serializeMessage($message);

        $this->assertSame(
            '{"id":1,"chat_id":1,"attempts":3,"text":"Привет","buttons":[]}',
            $payload,
        );
    }

    /**
     * @throws JsonException
     */
    public function testSerializesReportWithDbFormattedStartDate(): void
    {
        $report = Report::restoreFromPersistence(
            9,
            12345,
            new DateTimeImmutable('2023-01-05 00:00:00'),
            1,
        );

        $decoded = $this->decode($this->serializer->serializeReport($report));

        $this->assertSame(9, $decoded['id']);
        $this->assertSame(12345, $decoded['chat_id']);
        $this->assertSame(1, $decoded['attempts']);
        $this->assertSame('2023-01-05', $decoded['start_date']);
    }

    /**
     * @throws JsonException
     */
    public function testReportStartDateIsNotUserFacingFormat(): void
    {
        $report = Report::restoreFromPersistence(
            9,
            12345,
            new DateTimeImmutable('2023-01-05 00:00:00'),
            0,
        );

        $payload = $this->serializer->serializeReport($report);

        $this->assertStringNotContainsString('05.01.2023', $payload);
        $this->assertStringContainsString('"start_date":"2023-01-05"', $payload);
    }

    /**
     * @throws JsonException
     */
    public function testSerializesFreshReportWithNullId(): void
    {
        $report = Report::create(12345, new DateTimeImmutable('2023-01-05 00:00:00'));

        $decoded = $this->decode($this->serializer->serializeReport($report));

        $this->assertNull($decoded['id']);
        $this->assertSame(0, $decoded['attempts']);
    }

    /**
     * @return array<string, mixed>
     * @throws JsonException
     */
    private function decode(string $payload): array
    {
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);

        return $decoded;
    }
}
