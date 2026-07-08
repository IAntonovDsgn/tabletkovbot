<?php

namespace App\Infrastructure\Services\Telegram;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final class TelegramFacade
{
    private static Client $httpClient;
    private static bool $isInitialized = false;
    private static string $token;

    public static function init(array $config): void
    {
        if (self::$isInitialized) {
            return;
        }
        $baseUrl = $config['base_url'];
        self::$token = $config['token'];
        self::$httpClient = new Client(['base_uri' => $baseUrl]);
        self::$isInitialized = true;
    }

    /**
     * @return array<string,mixed>
     *
     * @throws GuzzleException
     * @throws TelegramException
     */
    public static function sendMessage(
        string $userId,
        string $message,
        ?int $replyToMessageId = null,
        ?string $parseMode = 'html',
    ): array {
        if (!self::$isInitialized) {
            throw new TelegramException('Telegram Facade is not initialized');
        }

        $requestProperties = [
            'chat_id' => $userId,
            'text' => $message,
            'parse_mode' => $parseMode,
            'reply_to_message_id' => $replyToMessageId,
        ];

        $response = self::$httpClient->post('/bot' . self::$token . '/sendMessage', $requestProperties)->getBody();
        return json_decode($response, true);
    }

    /**
     * @return array<string,mixed>
     *
     * @throws GuzzleException
     * @throws TelegramException
     */
    public static function getUpdates(): array
    {
        if (!self::$isInitialized) {
            throw new TelegramException('Telegram Facade is not initialized');
        }

        $response = self::$httpClient->post('/bot' . self::$token . '/getUpdates')->getBody();
        return json_decode($response, true);
    }
}
