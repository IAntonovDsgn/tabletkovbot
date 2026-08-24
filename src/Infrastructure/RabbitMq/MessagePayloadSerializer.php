<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\Message;
use JsonException;

final readonly class MessagePayloadSerializer
{
    /**
     * @throws JsonException
     */
    public function serialize(Message $message): string
    {
        return json_encode(
            [
                'id' => $message->getId(),
                'chat_id' => $message->getChatId(),
                'text' => $message->getText(),
                'buttons' => $message->getButtons(),
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );
    }
}
