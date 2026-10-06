<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMq;

use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;
use App\Domain\Support\DateFormats;
use App\Infrastructure\Dbal\Repositories\MessageOutboxRepository;
use App\Infrastructure\Dbal\Repositories\ReportOutboxRepository;
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
                MessageOutboxRepository::ID_COLUMN_NAME => $message->getId(),
                MessageOutboxRepository::CHAT_ID_COLUMN_NAME => $message->getChatId(),
                MessageOutboxRepository::ATTEMPTS_COLUMN_NAME => $message->getAttempts(),
                MessageOutboxRepository::TEXT_COLUMN_NAME => $message->getText(),
                MessageOutboxRepository::BUTTONS_COLUMN_NAME => $message->getButtons(),
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
                ReportOutboxRepository::ID_COLUMN_NAME => $report->getId(),
                ReportOutboxRepository::CHAT_ID_COLUMN_NAME => $report->getChatId(),
                ReportOutboxRepository::START_DATE_COLUMN_NAME => $report->getStartDate()->format(DateFormats::DATE),
                ReportOutboxRepository::ATTEMPTS_COLUMN_NAME => $report->getAttempts(),
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );
    }
}
