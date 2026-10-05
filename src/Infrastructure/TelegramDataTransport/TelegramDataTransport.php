<?php

declare(strict_types=1);

namespace App\Infrastructure\TelegramDataTransport;

use App\Application\Services\DataSender\DataTransportInterface;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use Exception;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;

final readonly class TelegramDataTransport implements DataTransportInterface
{
    private const string MESSAGE_PREFIX = '🐸 ';
    private const string ERROR_PREFIX = '❌ ';

    public function __construct(
        private TelegramBotApi $telegramApi,
    ) {}

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

                $keyboard->row([['text' => $button->getTitle(), 'callback_data' => $callbackData]]);
            }
        }

        try {
            $this->telegramApi->sendMessage([
                'chat_id' => $message->getChatId(),
                'text' => $this->decorateText($message->getText()),
                'reply_markup' => $keyboard,
            ]);
        } catch (TelegramSDKException $e) {
            throw new SendMessageException($e->getMessage());
        }
    }

    /**
     * @throws SendMessageException
     */
    public function sendFile(string $filePath, int $chatId): void
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new SendMessageException('File is not readable: ' . $filePath);
        }

        try {
            $this->telegramApi->sendDocument([
                'chat_id' => $chatId,
                'document' => InputFile::create($filePath, basename($filePath)),
            ]);
        } catch (TelegramSDKException $e) {
            throw new SendMessageException($e->getMessage());
        }
    }

    private function decorateText(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $enum = EnumMessageText::tryFrom($text);
        $prefix = $enum !== null && $enum->isError()
            ? self::ERROR_PREFIX
            : self::MESSAGE_PREFIX;

        return $prefix . $text;
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
