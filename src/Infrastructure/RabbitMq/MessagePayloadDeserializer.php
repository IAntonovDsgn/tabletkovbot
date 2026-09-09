<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use JsonException;

final readonly class MessagePayloadDeserializer
{
    /**
     * @throws JsonException
     */
    public function deserialize(string $payload): Message
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new JsonException('Payload must be a JSON object.');
        }

        return Message::restoreFromPersistence(
            $this->requireInt($data, 'id'),
            $this->requireInt($data, 'chat_id'),
            $this->optionalText($data),
            $this->buttons($data),
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
     * @param array<array-key, mixed> $data
     * @throws JsonException
     */
    private function optionalText(array $data): ?string
    {
        $text = $data['text'] ?? null;
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
