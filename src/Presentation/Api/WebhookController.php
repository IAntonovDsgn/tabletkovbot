<?php

declare(strict_types=1);

namespace App\Presentation\Api;

use App\Application\BotManager\Manager;
use App\Application\BotManager\RequestDTO;
use Psr\Log\LoggerInterface;
use OpenApi\Attributes as OA;
use Telegram\Bot\Api;
use Throwable;

#[OA\Info(
    version: "1.0.0",
    description: "API для взаимодействия с Telegram ботом TabletkovBot",
    title: "Tabletkov Bot API"
)]
final readonly class WebhookController
{
    public function __construct(
        private Api $telegramApi,
        private Manager $manager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws Throwable
     */
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
        $update = $this->telegramApi->getWebhookUpdate();

        if ($update->has('callback_query')) {
            $callbackQuery = $update->callbackQuery;
            if ($callbackQuery === null || $callbackQuery->message === null) {
                return;
            }

            $this->telegramApi->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id
            ]);

            $chatId = $callbackQuery->message->chat->id;
            $requestDTO = new RequestDTO($chatId, null, $callbackQuery->data);
        } elseif ($update->has('message')) {
            $message = $update->message;
            if ($message === null) {
                return;
            }

            $chatId = $message->chat->id;
            $requestDTO = new RequestDTO($chatId, $message->text);
        } else {
            return;
        }

        try {
            $this->manager->process($requestDTO);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

    }
}
