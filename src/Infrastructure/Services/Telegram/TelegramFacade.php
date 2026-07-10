<?php

namespace App\Infrastructure\Services\Telegram;

use App\Domain\Telegram\TelegramFacadeInterface;
use App\Infrastructure\ServiceContainer\ServiceContainer;
use DI\DependencyException;
use DI\NotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final readonly class TelegramFacade implements TelegramFacadeInterface
{
    private Client $httpClient;
    private string $token;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct() {
        $telegramConfig = ServiceContainer::get('telegram.config');
        $this->token = $telegramConfig['token'];
        $this->httpClient = new Client(['base_uri' => $telegramConfig['base_url']]);
    }

    /**
     * @throws GuzzleException
     * @throws TelegramException
     */
    public function sendMessage(
        string $chatId,
        string $message,
        ?int $replyToMessageId = null,
        ?string $parseMode = 'html',
    ): void {
        $requestProperties = [
            'form_params' => [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
                'reply_to_message_id' => $replyToMessageId,
            ]
        ];

        $response = $this->httpClient->post('/bot' . $this->token . '/sendMessage', $requestProperties);
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new TelegramException('Telegram Facade returned status code ' . $statusCode);
        }
    }

    /**
     * @throws GuzzleException
     * @throws TelegramException
     */
    public function getUpdates(): array
    {
        $response = $this->httpClient->post('/bot' . $this->token . '/getUpdates')->getBody();
        return json_decode($response, true);
    }
}
