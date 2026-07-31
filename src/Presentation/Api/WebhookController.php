<?php

namespace App\Presentation\Api;

use App\Application\BotManager\RequestDTO;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Telegram\Bot\Api;

#[OA\Info(
    version: "1.0.0",
    description: "API для взаимодействия с Telegram ботом TabletkovBot",
    title: "Tabletkov Bot API"
)]
final readonly class WebhookController
{
    public function __construct(private Api $telegramApi)
    {
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
        description: 'Успешная обработка'
    )]
    #[OA\Response(
        response: 500,
        description: 'Внутренняя ошибка при обработке запроса'
    )]
    public function handle(): void
    {
        try {
            $update = $this->telegramApi->getWebhookUpdate();

            if ($update->has('callback_query')) {
                $callbackQuery = $update->getCallbackQuery();

                $this->telegramApi->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId()
                ]);

                $chatId = $callbackQuery->getMessage()->getChat()->getId();
                $newState = $callbackQuery->getData();

                $requestDTO = new RequestDTO($chatId, null, $newState);
            }

            if (!$update->has('message')) {
                $message = $update->getMessage();
                Log::error('Message: ' . $message);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            http_response_code(500);
        }
    }
}
