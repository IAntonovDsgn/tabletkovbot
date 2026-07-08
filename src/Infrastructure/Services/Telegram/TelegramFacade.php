<?php

namespace App\Infrastructure\Services\Telegram;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

final class TelegramFacade
{
    private static string $baseUrl;
    private static string $token;

    public function __construct()
    {
        self::$baseUrl = Config::get('services.telegram.base_url');
        self::$token = Config::get('services.telegram.token');
    }

    public static function sendMessage(
        string $userId,
        string $message,
        ?int $replyToMessageId = null,
        ?string $parseMode = 'html',
    ): array {
        $requestUrl = self::$baseUrl . "/bot" . self::$token . "/sendMessage";
        $requestProperties = [
            'chat_id' => $userId,
            'text' => $message,
            'parse_mode' => $parseMode,
            'reply_to_message_id' => $replyToMessageId,
        ];

        return Http::timeout(10)->post($requestUrl, $requestProperties)->throw()->json();
    }

    /**
     * @return array<string,mixed>
     */
    public function getUpdates(): array
    {
        $requestUrl = self::$baseUrl . "/bot" . self::$token . "/getUpdates";
        return Http::timeout(10)->post($requestUrl)->throw()->json();
    }
}
