<?php

namespace App\Infrastructure\Services\TelegramMessage;

use App\Domain\Message\Message;
use App\Domain\Message\MessageFacadeInterface;
use App\Infrastructure\Facade\ServiceContainer\ServiceContainer;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use GuzzleHttp\Client;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final readonly class TelegramMessageFacade implements MessageFacadeInterface
{
    private Client $httpClient;
    private string $token;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(
        private Api $telegramApi,
    ) {
        $telegramConfig = ServiceContainer::get('telegram.config');
        $this->token = $telegramConfig['token'];
        $this->httpClient = new Client(['base_uri' => $telegramConfig['base_url']]);
    }

    /**
     * @throws TelegramMessageException
     */
    public function sendMessage(Message $message): void
    {
        try {
            $this->telegramApi->sendMessage([
                'chat_id' => $message->getChatId(),
                'text' => $message->getText(),
            ]);
        } catch (TelegramSDKException $e) {
            throw new TelegramMessageException($e->getMessage());
        }
    }

    /**
     * @throws TelegramMessageException
     */
    public function getUpdates(): array
    {
        $result = [];

        try {
            $updates = $this->telegramApi->getUpdates();
            foreach ($updates as $update) {
                $result[] = $update->getMessage();
            }
        } catch (Exception $e) {
            throw new TelegramMessageException($e->getMessage());
        }

        return $result;
    }
}
