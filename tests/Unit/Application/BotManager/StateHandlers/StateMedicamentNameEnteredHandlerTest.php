<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\StateManager\DTOs\StateHandlerResponseDTO;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\UseCases\StateMedicamentNameEnteredHandler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\States\EnumState;
use PHPUnit\Framework\TestCase;

class StateMedicamentNameEnteredHandlerTest extends TestCase
{
    /**
     * @throws InvalidValueException
     */
    public function testHandleSuccess(): void
    {
        $medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $handler = new StateMedicamentNameEnteredHandler($medicamentRepository);

        $chatId = 12345;
        $medicamentName = 'Aspirin';
        $newMedicamentId = 1;

        $medicamentRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Medicament $medicament) use ($chatId, $medicamentName) {
                return $medicament->getName() === $medicamentName && $medicament->getChatId() === $chatId;
            }))
            ->willReturn($newMedicamentId);

        $response = $handler->handle(Session::create($chatId), $medicamentName, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::ENTER_TIME,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ],
            (string) $newMedicamentId
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleThrowsExceptionOnNullText(): void
    {
        $medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $handler = new StateMedicamentNameEnteredHandler($medicamentRepository);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::MEDICAMENT_EMPTY_NAME_ERROR->value);

        $handler->handle(Session::create(12345), null, null);
    }
}
