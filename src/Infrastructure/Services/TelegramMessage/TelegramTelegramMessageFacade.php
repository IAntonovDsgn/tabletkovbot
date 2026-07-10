<?php

namespace App\Infrastructure\Services\TelegramMessage;

use App\Domain\TelegramMessage\TelegramMessage;
use App\Domain\TelegramMessage\TelegramMessageFacadeInterface;
use App\Infrastructure\ServiceContainer\ServiceContainer;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final readonly class TelegramTelegramMessageFacade implements TelegramMessageFacadeInterface
{
    private Client $httpClient;
    private string $token;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct()
    {
        $telegramConfig = ServiceContainer::get('telegram.config');
        $this->token = $telegramConfig['token'];
        $this->httpClient = new Client(['base_uri' => $telegramConfig['base_url']]);
    }

    /**
     * @throws GuzzleException
     * @throws TelegramException
     */
    public function sendMessage(TelegramMessage $message): void
    {
        $requestProperties = [
            'form_params' => [
                'chat_id' => $message->getChatId(),
                'text' => $message->getText(),
                'parse_mode' => 'html',
            ]
        ];

        $response = $this->httpClient->post('/bot' . $this->token . '/sendMessage', $requestProperties);
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new TelegramException('TelegramMessage Facade returned status code ' . $statusCode);
        }
    }

    /**
     * @throws GuzzleException
     * @throws TelegramException
     */
    public function getUpdates(): array
    {
        $result = [];
        $response = $this->httpClient->post('/bot' . $this->token . '/getUpdates')->getBody();
        $messages = json_decode($response, true)['result'];

        foreach ($messages as $message) {
            try {
                $result[] = new TelegramMessage(
                    $message['message']['chat']['id'],
                    $message['message']['text'],
                    $message['message']['message_id'],
                );
            } catch (Exception) {
                throw new TelegramException('Incorrect message');
            }
        }

        return $result;
    }
}
