<?php

use App\Application\Command\IntakeMark\CreateIntakeMark\CreateIntakeMarkException;
use App\Application\Command\IntakeMark\CreateIntakeMark\Handler;
use App\Domain\Chat\Chat;
use App\Domain\Chat\ChatRepositoryInterface;
use App\Domain\IntakeMark\IntakeMark;
use App\Domain\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentRepositoryInterface;

test('', function (bool $isChatExist, bool $isMedicamentExist, bool $isMedicamentActive) {
    $medicamentId = 1;
    $chatId = 1;

    $intakeMarkRepositoryMock = Mockery::mock(IntakeMarkRepositoryInterface::class);
    $chatRepositoryMock = Mockery::mock(ChatRepositoryInterface::class);
    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);

    $chat = new Chat($chatId);
    $medicament = null;

    if ($isMedicamentExist) {
        $medicament = new Medicament('testMedicament', $chatId);
        if (! $isMedicamentActive) {
            $medicament->deactivate();
        }
    }

    $chatRepositoryMock
        ->shouldReceive('findById')
        ->with($chatId)
        ->andReturn($isChatExist ? $chat : null);


    $medicamentRepositoryMock
        ->shouldReceive('findById')
        ->with($medicamentId)
        ->andReturn($medicament);


    if ($isChatExist && $isMedicamentExist && $isMedicamentActive) {
        $intakeMark = new IntakeMark($chatId, $medicamentId);
        $intakeMarkRepositoryMock
            ->shouldReceive('save')
            ->with($intakeMark)
            ->andReturn();
    }

    $handler = new Handler($intakeMarkRepositoryMock, $medicamentRepositoryMock, $chatRepositoryMock);

    if ($isChatExist && $isMedicamentExist && $isMedicamentActive) {
        expect(fn() => $handler($medicamentId, $chatId))
            ->not->toThrow(Throwable::class);
    } else {
        expect(fn() => $handler($medicamentId, $chatId))
            ->toThrow(CreateIntakeMarkException::class);
    }
})->with('create intake mark');

dataset('create intake mark', [
    'chat exist, medicament exist, medicament is active - метод не выбрасывает исключение' => [true, true, true],
    'chat not exist, medicament exist, medicament is active - метод выбрасывает исключение' => [false, true, true],
    'chat exist, medicament not exist, medicament is active - метод выбрасывает исключение' => [true, false, true],
    'chat exist, medicament exist, medicament is not active - метод выбрасывает исключение' => [true, true, false],
    'chat not exist, medicament not exist, medicament is active - метод выбрасывает исключение' => [false, false, true],
    'chat not exist, medicament exist, medicament is not active - метод выбрасывает исключение' => [false, true, false],
    'chat exist, medicament not exist, medicament is not active - метод выбрасывает исключение' => [true, false, false],
]);
