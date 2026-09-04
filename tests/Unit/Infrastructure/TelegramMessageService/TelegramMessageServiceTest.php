<?php

namespace Tests\Unit\Infrastructure\TelegramMessageService;

use App\Application\BotManager\RequestDTO;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Infrastructure\Exceptions\SendMessageException;
use App\Infrastructure\TelegramMessageService\TelegramMessageService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message as TelegramMessage;
use Telegram\Bot\Objects\Update;

class TelegramMessageServiceTest extends TestCase
{
    private MockObject $telegramApi;
    private TelegramMessageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->telegramApi = $this->createMock(TelegramBotApi::class);
        $this->service = new TelegramMessageService($this->telegramApi);
    }

    public function testSendMessageWithNoButtons(): void
    {
        $chatId = 123;
        $text = EnumMessageText::MENU->value;
        $message = Message::create($chatId, $text, []);

        $this->telegramApi->expects($this->once())
            ->method('sendMessage')
            ->with([
                'chat_id' => $chatId,
                'text' => '🐸 ' . $text,
                'reply_markup' => null,
            ]);

        $this->service->sendMessage($message);
    }

    public function testSendMessageWithButtons(): void
    {
        $chatId = 123;
        $text = EnumMessageText::CHOOSE_MEDICAMENT->value;
        $button1 = new MessageButton('Button 1', \App\Domain\Entities\Session\State\EnumState::ADD_MEDICAMENT_SELECTED);
        $button2 = new MessageButton('Button 2', \App\Domain\Entities\Session\State\EnumState::CHANGE_MEDICAMENT_SELECTED, 'payload');
        $message = Message::create($chatId, $text, [$button1, $button2]);

        $keyboard = Keyboard::make()->inline();
        $keyboard->row([['text' => $button1->getTitle(), 'callback_data' => $button1->getNewState()]]);
        $keyboard->row([['text' => $button2->getTitle(), 'callback_data' => $button2->getNewState() . MessageButton::PAYLOAD_SEPARATOR . $button2->getAdditionalPayload()]]);

        $this->telegramApi->expects($this->once())
            ->method('sendMessage')
            ->with([
                'chat_id' => $chatId,
                'text' => '🐸 ' . $text,
                'reply_markup' => $keyboard,
            ]);

        $this->service->sendMessage($message);
    }

    public function testErrorMessageGetsCrossPrefix(): void
    {
        $chatId = 123;
        $text = EnumMessageText::INTERNAL_ERROR->value;
        $message = Message::create($chatId, $text);

        $this->telegramApi->expects($this->once())
            ->method('sendMessage')
            ->with([
                'chat_id' => $chatId,
                'text' => '❌ ' . $text,
                'reply_markup' => null,
            ]);

        $this->service->sendMessage($message);
    }

    public function testEmptyTextWithButtonsIsSentWithoutAnyPrefix(): void
    {
        $chatId = 123;
        $button = new MessageButton('Button 1', \App\Domain\Entities\Session\State\EnumState::ADD_MEDICAMENT_SELECTED);
        $message = Message::create($chatId, null, [$button]);

        $keyboard = Keyboard::make()->inline();
        $keyboard->row([['text' => $button->getTitle(), 'callback_data' => $button->getNewState()]]);

        $this->telegramApi->expects($this->once())
            ->method('sendMessage')
            ->with([
                'chat_id' => $chatId,
                'text' => '',
                'reply_markup' => $keyboard,
            ]);

        $this->service->sendMessage($message);
    }

    public function testSendMessageThrowsExceptionOnTelegramSDKException(): void
    {
        $message = Message::create(123, 'Test');

        $this->telegramApi->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(new TelegramSDKException());

        $this->expectException(SendMessageException::class);
        $this->service->sendMessage($message);
    }

    public function testGetUpdatesReturnsRequestDTOs(): void
    {
        $chatId1 = 111;
        $text1 = 'Hello';
        $chatId2 = 222;
        $text2 = 'World';

        $telegramMessage1 = new TelegramMessage(['chat' => new Chat(['id' => $chatId1]), 'text' => $text1]);
        $telegramMessage2 = new TelegramMessage(['chat' => new Chat(['id' => $chatId2]), 'text' => $text2]);

        $update1 = new Update(['message' => $telegramMessage1]);
        $update2 = new Update(['message' => $telegramMessage2]);
        // Update with no message
        $update3 = new Update(['update_id' => 3]);

        $this->telegramApi->expects($this->once())
            ->method('getUpdates')
            ->willReturn([$update1, $update2, $update3]);

        $requestDTOs = $this->service->getUpdates();

        $this->assertCount(2, $requestDTOs);
        $this->assertEquals(new RequestDTO($chatId1, $text1), $requestDTOs[0]);
        $this->assertEquals(new RequestDTO($chatId2, $text2), $requestDTOs[1]);
    }

    public function testGetUpdatesThrowsExceptionOnTelegramSDKException(): void
    {
        $this->telegramApi->expects($this->once())
            ->method('getUpdates')
            ->willThrowException(new TelegramSDKException());

        $this->expectException(SendMessageException::class);
        $this->service->getUpdates();
    }
}
