<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateNotificationEnabledHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\SessionRepositoryInterface;
use PHPUnit\Framework\TestCase;

class StateNotificationEnabledHandlerTest extends TestCase
{
    private SessionRepositoryInterface $sessionRepository;
    private KeyboardFactory $keyboardFactory;
    private StateNotificationEnabledHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateNotificationEnabledHandler(
            $this->sessionRepository,
            $this->keyboardFactory
        );
    }

    public function testHandleWithExistingSession(): void
    {
        $chatId = 12345;
        $session = Session::create($chatId);
        $session->disableNotifications(); // Start with them disabled

        $this->sessionRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($session);

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Session $savedSession) {
                return $savedSession->isNotificationEnabled();
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
                return $savedSession->getChatId() === $chatId && $savedSession->isNotificationEnabled();
            }));

        $response = $this->handler->handle($chatId, null, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::SETTINGS_SAVED,
            $this->keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }
}
