<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;
use JsonException;

final readonly class Serializer
{
    /**
     * @throws JsonException
     */
    public function serializeMessage(Message $message): string
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

    /**
     * @throws JsonException
     */
    public function serializeReport(Report $report): string
    {
        return json_encode(
            [
                'id' => $report->getId(),
                'chat_id' => $report->getChatId(),
                'text' => $report->getStartDate(),
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );
    }
}
