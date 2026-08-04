<?php

namespace App\Infrastructure\TelegramMessageService;

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Message\Message;
use App\Domain\Exceptions\Interior\SendMessageException;
use Exception;
use GuzzleHttp\Client as HttpClient;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

final readonly class TelegramMessageService implements MessageServiceInterface
{
    private HttpClient $httpClient;
    private string $token;

    public function __construct(
        private TelegramBotApi $telegramApi,

    ) {
        $this->token =
        $this->httpClient = new HttpClient(['base_uri' => $telegramConfig['base_url']]);
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
                    MessageButton::PAYLOAD_SEPARATOR,
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
