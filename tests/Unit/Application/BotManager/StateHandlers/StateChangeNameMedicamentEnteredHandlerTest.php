<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\DTOs\StateHandlerResponseDTO;
use App\Application\BotManager\Exceptions\InvalidValueException;
use App\Application\BotManager\Factories\KeyboardFactory;
use App\Application\BotManager\StateHandlers\StateChangeNameMedicamentEnteredHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use App\Domain\Exceptions\NotFoundEntityException;
use DateTimeImmutable;
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

    /**
     * @throws InvalidValueException
     * @throws NotFoundEntityException
     */
    public function testHandleSuccess(): void
    {
        $chatId = 12345;
        $medicamentId = 1;
        $newName = 'Paracetamol';

        $medicament = Medicament::restoreFromPersistence(
            $medicamentId,
            'Aspirin',
            $chatId,
            new DateTimeImmutable(),
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

        $session = Session::create($chatId, payload: (string) $medicamentId);
        $response = $this->handler->handle($session, $newName, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_RENAMED_SUCCESS,
            $this->keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @throws NotFoundEntityException
     */
    public function testHandleThrowsExceptionOnNullName(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_EMPTY_NAME_ERROR->value);

        $session = Session::create(12345, payload: '1');
        $this->handler->handle($session, null, null);
    }

    /**
     * @throws InvalidValueException
     */
    public function testHandleThrowsExceptionIfMedicamentNotFound(): void
    {
        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $session = Session::create(12345, payload: '1');
        $this->handler->handle($session, 'New Name', null);
    }

    /**
     * @throws InvalidValueException
     */
    public function testHandleThrowsExceptionIfMedicamentBelongsToAnotherChat(): void
    {
        $chatId = 12345;
        $anotherChatId = 54321;
        $medicamentId = 1;

        $medicament = Medicament::restoreFromPersistence(
            $medicamentId,
            'Aspirin',
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

        $session = Session::create($chatId, payload: (string) $medicamentId);
        $this->handler->handle($session, 'New Name', null);
    }
}
