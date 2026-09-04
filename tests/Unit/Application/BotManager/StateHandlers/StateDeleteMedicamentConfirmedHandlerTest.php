<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\KeyboardFactory;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\BotManager\StateHandlers\StateDeleteMedicamentConfirmedHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Session\Session;
use App\Domain\Exceptions\NotFoundEntityException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StateDeleteMedicamentConfirmedHandlerTest extends TestCase
{
    private MedicamentRepositoryInterface $medicamentRepository;
    private KeyboardFactory $keyboardFactory;
    private StateDeleteMedicamentConfirmedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateDeleteMedicamentConfirmedHandler(
            $this->medicamentRepository,
            $this->keyboardFactory
        );
    }

    /**
     * @throws NotFoundEntityException
     */
    public function testHandleSuccess(): void
    {
        $chatId = 12345;
        $medicamentId = 1;

        $medicament = Medicament::restoreFromPersistence(
            $medicamentId,
            'Aspirin',
            $chatId,
            new DateTimeImmutable(),
            true
        );

        $this->assertTrue($medicament->isActive());

        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with($medicamentId)
            ->willReturn($medicament);

        $this->medicamentRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Medicament $savedMedicament) {
                return !$savedMedicament->isActive();
            }));

        $session = Session::create($chatId, payload: (string) $medicamentId);
        $response = $this->handler->handle($session, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::MEDICAMENT_DELETED,
            $this->keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleThrowsExceptionIfMedicamentNotFound(): void
    {
        $this->medicamentRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(NotFoundEntityException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_NOT_FOUND->value);

        $session = Session::create(12345, payload: '1');
        $this->handler->handle($session, null, null);
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
        $this->handler->handle($session, null, null);
    }
}
