<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateMedicamentNotificationTimeEnteredHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Exceptions\External\InvalidValueException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StateMedicamentNotificationTimeEnteredHandlerTest extends TestCase
{
    private MedicamentRepositoryInterface $medicamentRepository;
    private KeyboardFactory $keyboardFactory;
    private StateMedicamentNotificationTimeEnteredHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory(); // Real factory as it has no external dependencies
        $this->handler = new StateMedicamentNotificationTimeEnteredHandler(
            $this->medicamentRepository,
            $this->keyboardFactory
        );
    }

    public function testHandleSuccess(): void
    {
        $chatId = 12345;
        $medicamentId = 1;
        $time = '09:30';

        $medicament = Medicament::restoreFromPersistence(
            $medicamentId,
            'Test', $chatId,
            new DateTimeImmutable(),
            true
        );

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with($medicamentId)
            ->willReturn($medicament);

        $this->medicamentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Medicament $savedMedicament) use ($time) {
                return $savedMedicament->getNotificationTime()?->format('H:i') === $time;
            }));

        $response = $this->handler->handle($chatId, $time, (string)$medicamentId, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_ADDED_SUCCESS,
            $this->keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleThrowsExceptionForInvalidTimeFormat(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::FORMAT_TIME_ERROR->value);

        $this->handler->handle(12345, 'invalid-time', '1', null);
    }

    public function testHandleThrowsExceptionForNullTime(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::FORMAT_TIME_ERROR->value);

        $this->handler->handle(12345, null, '1', null);
    }

    public function testHandleThrowsExceptionIfMedicamentNotFound(): void
    {
        $medicamentId = 1;
        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with($medicamentId)
            ->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(12345, '09:30', (string)$medicamentId, null);
    }

    public function testHandleThrowsExceptionIfMedicamentBelongsToAnotherChat(): void
    {
        $chatId = 12345;
        $anotherChatId = 54321;
        $medicamentId = 1;

        $medicament = Medicament::restoreFromPersistence(
            $medicamentId,
            'Test',
            $anotherChatId,
            new DateTimeImmutable(),
            true
        );

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with($medicamentId)
            ->willReturn($medicament);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle($chatId, '09:30', (string)$medicamentId, null);
    }
}
