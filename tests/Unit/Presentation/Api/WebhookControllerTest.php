<?php

namespace Tests\Unit\Presentation\Api;

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
use Psr\Log\LoggerInterface;
use RuntimeException;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message as TelegramMessage;
use Telegram\Bot\Objects\Update;
use Throwable;

class WebhookControllerTest extends TestCase
{
    private MockObject $telegramApi;
    private MockObject $stateHandlerFactory;
    private MockObject $sessionRepository;
    private MockObject $outboxRepository;
    private MockObject $unitOfWork;
    private MockObject $logger;
    private WebhookController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->telegramApi = $this->createMock(TelegramBotApi::class);
        $this->stateHandlerFactory = $this->createMock(StateHandlerFactory::class);
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(OutboxRepositoryInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWorkInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $manager = new Manager(
            $this->stateHandlerFactory,
            $this->sessionRepository,
            $this->outboxRepository,
            $this->unitOfWork,
        );

        $this->controller = new WebhookController($this->telegramApi, $manager, $this->logger);
    }

    private function givenWebhookUpdate(Update $update): void
    {
        $this->telegramApi->method('getWebhookUpdate')->willReturn($update);
    }

    private function messageUpdate(int $chatId): Update
    {
        return new Update([
            'message' => new TelegramMessage([
                'message_id' => 1,
                'chat' => new Chat(['id' => $chatId]),
                'text' => 'start',
            ]),
        ]);
    }

    private function callbackQueryUpdate(int $chatId, string $data): Update
    {
        return new Update([
            'callback_query' => new CallbackQuery([
                'id' => 'cb-1',
                'from' => ['id' => $chatId],
                'message' => new TelegramMessage([
                    'message_id' => 5,
                    'chat' => new Chat(['id' => $chatId]),
                ]),
                'data' => $data,
            ]),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testHandleRoutesTextMessageToManager(): void
    {
        $chatId = 123;
        $this->givenWebhookUpdate($this->messageUpdate($chatId));

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
            ->with(
                $this->callback(fn(Session $s) => $s->getChatId() === $chatId && $s->getState() === EnumState::MENU),
                'start',
                null,
            )
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

    /**
     * @throws Throwable
     */
    public function testHandleRoutesCallbackQueryWithAnswerAndPayload(): void
    {
        $chatId = 123;
        $payload = EnumState::ADD_MEDICAMENT_SELECTED->value;
        $this->givenWebhookUpdate($this->callbackQueryUpdate($chatId, $payload));

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
            ->with(
                $this->callback(fn(Session $s) => $s->getChatId() === $chatId && $s->getState() === EnumState::ADD_MEDICAMENT_SELECTED),
                null,
                null,
            )
            ->willReturn(new StateHandlerResponseDTO(EnumMessageText::ENTER_NEW_NAME, []));

        $this->sessionRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Session $session) {
                return $session->getState() === EnumState::ADD_MEDICAMENT_SELECTED;
            }));

        $this->outboxRepository->expects($this->once())->method('insert');

        $this->controller->handle();
    }

    /**
     * @throws Throwable
     */
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

    /**
     * @throws Throwable
     */
    public function testHandleIgnoresUpdateWithoutMessageAndCallbackQuery(): void
    {
        $this->givenWebhookUpdate(new Update(['update_id' => 3]));

        $this->telegramApi->expects($this->never())->method('answerCallbackQuery');
        $this->unitOfWork->expects($this->never())->method('begin');
        $this->sessionRepository->expects($this->never())->method('findByChatId');

        $this->controller->handle();
    }

    /**
     * @throws Throwable
     */
    public function testHandleLogsManagerExceptionAfterRollback(): void
    {
        $chatId = 123;
        $this->givenWebhookUpdate($this->messageUpdate($chatId));

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->never())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback');

        $this->sessionRepository->method('findByChatId')->willReturn(null);

        $stateHandler = $this->createMock(StateHandlerInterface::class);
        $this->stateHandlerFactory->method('makeByState')->willReturn($stateHandler);
        $stateHandler->method('handle')->willThrowException(new RuntimeException('boom'));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message) use ($chatId) {
                return $message->getChatId() === $chatId
                    && $message->getText() === EnumMessageText::INTERNAL_ERROR->value;
            }));

        $this->logger->expects($this->once())->method('error');

        $this->controller->handle();
    }
}
