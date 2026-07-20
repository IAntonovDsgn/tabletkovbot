<?php

namespace App\Infrastructure\Services\TelegramMessageService;

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\OutgoingMessageEnum;
use App\Domain\Exceptions\SentToClient\SendMessageException;
use App\Infrastructure\Facade\ServiceContainer\ContainerService;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use GuzzleHttp\Client;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final readonly class TelegramMessageService implements MessageServiceInterface
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
        $telegramConfig = ContainerService::get('telegram.config');
        $this->token = $telegramConfig['token'];
        $this->httpClient = new Client(['base_uri' => $telegramConfig['base_url']]);
    }

    /**
     * @throws SendMessageException
     */
    public function sendMessage($state): void
    {
        try {
            $this->telegramApi->sendMessage([
                'chat_id' => $message->getChatId(),
                'text' => $message->getText(),
            ]);
        } catch (TelegramSDKException $e) {
            throw new SendMessageException($e->getMessage());
        }
    }

    /**
     * @throws SendMessageException
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
            throw new SendMessageException($e->getMessage());
        }

        return $result;
    }
}
