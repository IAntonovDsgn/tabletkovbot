<?php

namespace App\Presentation\Api;

use App\Application\BotManager\RequestDTO;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Api;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "API для взаимодействия с Telegram ботом TabletkovBot",
    title: "Tabletkov Bot API"
)]
final readonly class WebhookController
{
    public function __construct(
        private Api $telegramApi,
        private LoggerInterface $logger
    ) {
    }

    #[OA\Post(
        path: '/webhook',
        summary: 'Входящий вебхук от серверов Telegram',
        tags: ['Webhook']
    )]
    #[OA\RequestBody(
        description: 'Объект Update от Telegram',
        required: true,
        content: new OA\JsonContent(
            example: [
                "update_id" => 123456789,
                "message" => [
                    "message_id" => 1,
                    "chat" => ["id" => 123456789, "type" => "private"],
                    "text" => "test message"
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Успешная обработка (или пропуск апдейта, если это не message)'
    )]
    #[OA\Response(
        response: 500,
        description: 'Внутренняя ошибка при обработке запроса'
    )]
    public function indexAction(): void
    {
        try {
            $update = $this->telegramApi->getWebhookUpdate();

            if (!$update->has('message')) {
                return;
            }

            $message = $update->getMessage();

            $incomingMessage = new RequestDTO(
                $message->getChat()->getId(),
            // можно добавить текст и другие нужные поля
            );
        } catch (\Throwable $e) {
            $this->logger->error('Ошибка при обработке запроса', ['exception' => $e]);
            http_response_code(500);
        }
    }
}
