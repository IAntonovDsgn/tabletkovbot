<?php

namespace App\Infrastructure\Services\TelegramMessageService;

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\Message;
use App\Domain\Exceptions\Interior\SendMessageException;
use Exception;
use GuzzleHttp\Client;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

final readonly class TelegramMessageService implements MessageServiceInterface
{
    private Client $httpClient;
    private string $token;

    public function __construct(
        private Api $telegramApi,
        private array $telegramConfig,
    ) {
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
                $callbackData = implode(
                    Button::PAYLOAD_SEPARATOR,
                    [
                        $button->getNewState(),
                        $button->getAdditionalPayload()
                    ]
                );
                $keyboards->row(['text' => $button->getTitle(), 'callback_data' => $callbackData]);
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
