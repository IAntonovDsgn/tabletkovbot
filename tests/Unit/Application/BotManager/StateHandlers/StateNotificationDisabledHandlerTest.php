<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateNotificationDisabledHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use PHPUnit\Framework\TestCase;

class StateNotificationDisabledHandlerTest extends TestCase
{
    private SessionRepositoryInterface $sessionRepository;
    private KeyboardFactory $keyboardFactory;
    private StateNotificationDisabledHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateNotificationDisabledHandler(
            $this->sessionRepository,
            $this->keyboardFactory
        );
    }

    public function testHandleWithExistingSession(): void
    {
        $chatId = 12345;
        $session = new Session($chatId);
        $session->enableNotifications(); // Start with them enabled

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($session);

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Session $savedSession) {
                return !$savedSession->isNotificationEnabled();
            }));

        $response = $this->handler->handle($chatId, null, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED,
            $this->keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleWithoutExistingSession(): void
    {
        $chatId = 12345;

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn(null);

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Session $savedSession) use ($chatId) {
                // A new session defaults to notifications enabled, so disabling it should result in false.
                return $savedSession->getChatId() === $chatId && !$savedSession->isNotificationEnabled();
            }));

        $response = $this->handler->handle($chatId, null, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED,
            $this->keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
