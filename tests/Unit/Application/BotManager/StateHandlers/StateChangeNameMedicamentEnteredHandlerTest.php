<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateChangeNameMedicamentEnteredHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Exceptions\External\InvalidValueException;
use App\Domain\Exceptions\Interior\NotFoundEntityException;
use PHPUnit\Framework\TestCase;

class StateChangeNameMedicamentEnteredHandlerTest extends TestCase
{
    private MedicamentRepositoryInterface $medicamentRepository;
    private KeyboardFactory $keyboardFactory;
    private StateChangeNameMedicamentEnteredHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateChangeNameMedicamentEnteredHandler(
            $this->medicamentRepository,
            $this->keyboardFactory
        );
    }

    public function testHandleSuccess(): void
    {
        $chatId = 12345;
        $medicamentId = 1;
        $newName = 'Paracetamol';

        $medicament = Medicament::restoreFromPersistence(
            $medicamentId,
            'Aspirin',
            $chatId,
            new \DateTimeImmutable(),
            true
        );

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with($medicamentId)
            ->willReturn($medicament);

        $this->medicamentRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Medicament $savedMedicament) use ($newName) {
                return $savedMedicament->getName() === $newName;
            }));

        $response = $this->handler->handle($chatId, $newName, (string)$medicamentId, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_RENAMED_SUCCESS,
            $this->keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleThrowsExceptionOnNullName(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_EMPTY_NAME_ERROR->value);

        $this->handler->handle(12345, null, '1', null);
    }

    public function testHandleThrowsExceptionIfMedicamentNotFound(): void
    {
        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle(12345, 'New Name', '1', null);
    }

    public function testHandleThrowsExceptionIfMedicamentBelongsToAnotherChat(): void
    {
        $chatId = 12345;
        $anotherChatId = 54321;
        $medicamentId = 1;

        $medicament = Medicament::restoreFromPersistence(
            $medicamentId,
            'Aspirin',
            $anotherChatId,
            new \DateTimeImmutable(),
            true
        );

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with($medicamentId)
            ->willReturn($medicament);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $this->handler->handle($chatId, 'New Name', (string)$medicamentId, null);
    }
}
