<?php

namespace App\Infrastructure\Services\TelegramMessageService;

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Exceptions\NotSendToClient\SendMessageException;
use App\Infrastructure\Services\ServiceContainer;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use GuzzleHttp\Client;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

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
        $telegramConfig = ServiceContainer::get('telegram.config');
        $this->token = $telegramConfig['token'];
        $this->httpClient = new Client(['base_uri' => $telegramConfig['base_url']]);
    }

    /**
     * @throws SendMessageException
     */
    public function sendMessage(Message $message): void
    {
        if (!empty($message->getButtons())) {
            $keyboards = Keyboard::make()->inline();
            foreach ($message->getButtons() as $button) {
                $keyboards->row(['text' => $button->getTitle(), 'callback_data' => $button->getNewState()]);
            }
        }

        try {
            $this->telegramApi->sendMessage([
                'chat_id' => $message->getChatId(),
                'text' => $message->getText(),
                'reply_markup' => $keyboard ?? null,
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
