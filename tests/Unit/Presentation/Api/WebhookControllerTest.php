<?php

namespace Tests\Unit\Presentation\Api;

use App\Application\BotManager\KeyboardFactory;
use App\Application\BotManager\Manager;
use App\Application\BotManager\StateHandlerFactory;
use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Outbox\OutboxRepositoryInterface;
use App\Application\UnitOfWork\UnitOfWorkInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use App\Domain\Entities\Session\State\EnumState;
use App\Presentation\Api\WebhookController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message as TelegramMessage;
use Telegram\Bot\Objects\Update;

class WebhookControllerTest extends TestCase
{
    private MockObject $telegramApi;
    private MockObject $stateHandlerFactory;
    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private MockObject $keyboardFactory;
    private MockObject $unitOfWork;
    private WebhookController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->telegramApi = $this->createMock(TelegramBotApi::class);
        $this->stateHandlerFactory = $this->createMock(StateHandlerFactory::class);
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(OutboxRepositoryInterface::class);
        $this->keyboardFactory = $this->createMock(KeyboardFactory::class);
        $this->unitOfWork = $this->createMock(UnitOfWorkInterface::class);

        $manager = new Manager(
            $this->stateHandlerFactory,
            $this->sessionRepository,
            $this->outboxRepository,
            $this->keyboardFactory,
            $this->unitOfWork
        );

        $this->controller = new WebhookController($this->telegramApi, $manager);
    }

    private function givenWebhookUpdate(Update $update): void
    {
        $this->telegramApi->method('getWebhookUpdate')->willReturn($update);
    }

    private function messageUpdate(int $chatId, ?string $text): Update
    {
        return new Update([
            'message' => new TelegramMessage([
                'message_id' => 1,
                'chat' => new Chat(['id' => $chatId]),
                'text' => $text,
            ]),
        ]);
    }

    private function callbackQueryUpdate(int $chatId, string $callbackQueryId, string $data): Update
    {
        return new Update([
            'callback_query' => new CallbackQuery([
                'id' => $callbackQueryId,
                'from' => ['id' => $chatId],
                'message' => new TelegramMessage([
                    'message_id' => 5,
                    'chat' => new Chat(['id' => $chatId]),
                ]),
                'data' => $data,
            ]),
        ]);
    }

    public function testHandleRoutesTextMessageToManager(): void
    {
        $chatId = 123;
        $this->givenWebhookUpdate($this->messageUpdate($chatId, 'start'));

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->once())->method('commit');
        $this->unitOfWork->expects($this->never())->method('rollback');

        $this->telegramApi->expects($this->never())->method('answerCallbackQuery');

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn(null);

        $stateHandler = $this->createMock(StateHandlerInterface::class);
        $this->stateHandlerFactory->expects($this->once())
            ->method('makeByState')
            ->with(EnumState::MENU)
            ->willReturn($stateHandler);

        $stateHandler->expects($this->once())
            ->method('handle')
            ->with($chatId, 'start', null, null)
            ->willReturn(new StateHandlerResponseDTO(EnumMessageText::MENU, []));

        $this->sessionRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Session $session) use ($chatId) {
                return $session->getChatId() === $chatId && $session->getState() === EnumState::MENU;
            }));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId
                    && $message->getText() === EnumMessageText::MENU->value;
            }));

        $this->controller->handle();
    }

    public function testHandleRoutesCallbackQueryWithAnswerAndPayload(): void
    {
        $chatId = 123;
        $payload = EnumState::ADD_MEDICAMENT_SELECTED->value;
        $this->givenWebhookUpdate($this->callbackQueryUpdate($chatId, 'cb-1', $payload));

        $this->telegramApi->expects($this->once())
            ->method('answerCallbackQuery')
            ->with(['callback_query_id' => 'cb-1']);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->once())->method('commit');
        $this->unitOfWork->expects($this->never())->method('rollback');

        $existingSession = Session::restoreFromPersistence(1, $chatId, true, EnumState::MENU);
        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($existingSession);

        $stateHandler = $this->createMock(StateHandlerInterface::class);
        $this->stateHandlerFactory->expects($this->once())
            ->method('makeByState')
            ->with(EnumState::ADD_MEDICAMENT_SELECTED)
            ->willReturn($stateHandler);

        $stateHandler->expects($this->once())
            ->method('handle')
            ->with($chatId, null, null, null)
            ->willReturn(new StateHandlerResponseDTO(EnumMessageText::ENTER_NEW_NAME, []));

        $this->sessionRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Session $session) {
                return $session->getState() === EnumState::ADD_MEDICAMENT_SELECTED;
            }));

        $this->outboxRepository->expects($this->once())->method('insert');

        $this->controller->handle();
    }

    public function testHandleIgnoresCallbackQueryWithoutMessage(): void
    {
        $this->givenWebhookUpdate(new Update([
            'callback_query' => new CallbackQuery([
                'id' => 'cb-2',
                'data' => EnumState::MENU->value,
            ]),
        ]));

        $this->telegramApi->expects($this->never())->method('answerCallbackQuery');
        $this->unitOfWork->expects($this->never())->method('begin');

        $this->controller->handle();
    }

    public function testHandleIgnoresUpdateWithoutMessageAndCallbackQuery(): void
    {
        $this->givenWebhookUpdate(new Update(['update_id' => 3]));

        $this->telegramApi->expects($this->never())->method('answerCallbackQuery');
        $this->unitOfWork->expects($this->never())->method('begin');
        $this->sessionRepository->expects($this->never())->method('findByChatId');

        $this->controller->handle();
    }

    public function testHandleRethrowsManagerExceptionAfterRollback(): void
    {
        $chatId = 123;
        $this->givenWebhookUpdate($this->messageUpdate($chatId, 'start'));

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->never())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback');

        $this->sessionRepository->method('findByChatId')->willReturn(null);

        $stateHandler = $this->createMock(StateHandlerInterface::class);
        $this->stateHandlerFactory->method('makeByState')->willReturn($stateHandler);
        $stateHandler->method('handle')->willThrowException(new RuntimeException('boom'));

        // notifyInternalError fallback message is persisted best-effort
        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId
                    && $message->getText() === EnumMessageText::INTERNAL_ERROR->value;
            }));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $this->controller->handle();
    }
}
