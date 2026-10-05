<?php

declare(strict_types=1);

namespace App\Application\Services\DataSender;

use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Message\Message;

interface DataTransportInterface
{
    public function sendMessage(Message $message): void;

    public function sendFile(string $filePath, int $chatId): void;

    /**
     * @return RequestDTO[]
     */
    public function getUpdates(): array;
}
