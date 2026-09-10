<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Report\Report;
use App\Domain\Entities\Session\States\EnumState;
use App\Infrastructure\Dbal\Repositories\MessageOutboxRepository;
use App\Infrastructure\Dbal\Repositories\ReportOutboxRepository;
use JsonException;

final readonly class Deserializer
{
    /**
     * @throws JsonException
     */
    public function deserializeMessage(string $payload): Message
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new JsonException('Payload must be a JSON object.');
        }

        return Message::restoreFromPersistence(
            $this->requireInt($data, MessageOutboxRepository::ID_COLUMN_NAME),
            $this->requireInt($data, MessageOutboxRepository::CHAT_ID_COLUMN_NAME),
            $this->requireInt($data, MessageOutboxRepository::ATTEMPTS_COLUMN_NAME),
            $this->optionalString($data, MessageOutboxRepository::TEXT_COLUMN_NAME),
            $this->buttons($data),
        );
    }

    /**
     * @throws JsonException
     */
    public function deserializeReport(string $payload): Report
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new JsonException('Payload must be a JSON object.');
        }

        return Report::restoreFromPersistence(
            $this->requireInt($data, ReportOutboxRepository::ID_COLUMN_NAME),
            $this->requireInt($data, ReportOutboxRepository::CHAT_ID_COLUMN_NAME),
            $this->requireDate($data, ReportOutboxRepository::START_DATE_COLUMN_NAME, Report::DATE_FORMAT),
            $this->requireInt($data, ReportOutboxRepository::ATTEMPTS_COLUMN_NAME),
        );
    }

    /**
     * @param array<array-key, mixed> $data
     * @throws JsonException
     */
    private function requireInt(array $data, string $key): int
    {
        if (!isset($data[$key]) || !is_int($data[$key])) {
            throw new JsonException(sprintf('Field "%s" must be an int.', $key));
        }

        return $data[$key];
    }

    /**
     * @throws JsonException
     */
    private function requireDate(array $data, string $key, string $dateFormat): \DateTimeImmutable
    {
        if (!isset($data[$key]) || !is_string($data[$key])) {
            throw new JsonException(sprintf('Field "%s" must be an string.', $key));
        }

        return \DateTimeImmutable::createFromFormat($dateFormat, $data[$key]);
    }

    /**
     * @param array<array-key, mixed> $data
     * @throws JsonException
     */
    private function optionalString(array $data, string $key): ?string
    {
        $text = $data[$key] ?? null;
        if ($text !== null && !is_string($text)) {
            throw new JsonException('Field "text" must be a string or null.');
        }

        return $text;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return MessageButton[]
     * @throws JsonException
     */
    private function buttons(array $data): array
    {
        $rawButtons = $data['buttons'] ?? [];
        if (!is_array($rawButtons)) {
            throw new JsonException('Field "buttons" must be an array.');
        }

        $buttons = [];
        foreach ($rawButtons as $rawButton) {
            if (!is_array($rawButton)
                || !isset($rawButton['title'], $rawButton['new_state'])
                || !is_string($rawButton['title'])
                || !is_string($rawButton['new_state'])
            ) {
                throw new JsonException('Every button must have string "title" and "new_state".');
            }

            $additionalPayload = $rawButton['additional_payload'] ?? null;
            if ($additionalPayload !== null && !is_string($additionalPayload)) {
                throw new JsonException('Field "additional_payload" must be a string or null.');
            }

            $state = EnumState::tryFrom($rawButton['new_state']);
            if ($state === null) {
                throw new JsonException(sprintf('Unknown state "%s".', $rawButton['new_state']));
            }

            $buttons[] = new MessageButton($rawButton['title'], $state, $additionalPayload);
        }

        return $buttons;
    }
}
