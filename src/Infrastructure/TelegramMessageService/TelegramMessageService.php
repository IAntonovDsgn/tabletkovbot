<?php

declare(strict_types=1);

namespace App\Infrastructure\TelegramMessageService;

use App\Application\BotManager\RequestDTO;
use App\Application\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Exceptions\Interior\SendMessageException;
use Exception;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

final readonly class TelegramMessageService implements MessageServiceInterface
{
    public function __construct(
        private TelegramBotApi $telegramApi,
    ) {
    }

    /**
     * @throws SendMessageException
     */
    public function sendMessage(Message $message): void
    {
        $keyboard = null;

        if (!empty($message->getButtons())) {
            $keyboard = Keyboard::make()->inline();
            foreach ($message->getButtons() as $button) {
                $callbackData = $button->getAdditionalPayload() !== null
                    ? $button->getNewState() . MessageButton::PAYLOAD_SEPARATOR . $button->getAdditionalPayload()
                    : $button->getNewState();

                $keyboard->row(['text' => $button->getTitle(), 'callback_data' => $callbackData]);
            }
        }

        try {
            $this->telegramApi->sendMessage([
                'chat_id' => $message->getChatId(),
                'text' => $message->getText(),
                'reply_markup' => $keyboard,
            ]);
        } catch (TelegramSDKException $e) {
            throw new SendMessageException($e->getMessage());
        }
    }

    /**
     * @return RequestDTO[]
     * @throws SendMessageException
     */
    public function getUpdates(): array
    {
        $result = [];

        try {
            $updates = $this->telegramApi->getUpdates();
            foreach ($updates as $update) {
                $message = $update->message;
                if ($message === null) {
                    continue;
                }

                $result[] = new RequestDTO(
                    $message->chat->id,
                    $message->text,
                );
            }
        } catch (Exception $e) {
            throw new SendMessageException($e->getMessage());
        }

        return $result;
    }
}
