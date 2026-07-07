<?php

use App\Application\Command\IntakeMark\CreateIntakeMark\CreateIntakeMarkException;
use App\Application\Command\IntakeMark\CreateIntakeMark\Handler;
use App\Domain\IntakeMark\IntakeMarkFactory;
use App\Domain\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Medicament\MedicamentFactory;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use App\Domain\User\UserFactory;
use App\Domain\User\UserRepositoryInterface;

test('', function (bool $isUserExist, bool $isMedicamentExist, bool $isMedicamentActive) {
    $medicamentId = 1;
    $userId = 1;
    $telegramId = 1;

    $intakeMarkRepositoryMock = Mockery::mock(IntakeMarkRepositoryInterface::class);
    $userRepositoryMock = Mockery::mock(UserRepositoryInterface::class);
    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);

    $user = UserFactory::create($telegramId);
    $medicament = null;

    if ($isMedicamentExist) {
        $medicament = MedicamentFactory::create('testMedicament', $userId);
        if (! $isMedicamentActive) {
            $medicament->deactivate();
        }
    }

    $userRepositoryMock
        ->shouldReceive('findByUserId')
        ->with($userId)
        ->andReturn($isUserExist ? $user : null);


    $medicamentRepositoryMock
        ->shouldReceive('findById')
        ->with($medicamentId)
        ->andReturn($medicament);


    if ($isUserExist && $isMedicamentExist && $isMedicamentActive) {
        $intakeMark = IntakeMarkFactory::create($userId, $medicamentId);
        $intakeMarkRepositoryMock
            ->shouldReceive('save')
            ->with($intakeMark)
            ->andReturn();
    }

    $handler = new Handler($intakeMarkRepositoryMock, $medicamentRepositoryMock, $userRepositoryMock);

    if ($isUserExist && $isMedicamentExist && $isMedicamentActive) {
        expect(fn() => $handler($medicamentId, $userId))
            ->not->toThrow(Throwable::class);
    } else {
        expect(fn() => $handler($medicamentId, $userId))
            ->toThrow(CreateIntakeMarkException::class);
    }
})->with('create intake mark');

dataset('create intake mark', [
    'user exist, medicament exist, medicament is active - метод не выбрасывает исключение' => [true, true, true],
    'user not exist, medicament exist, medicament is active - метод выбрасывает исключение' => [false, true, true],
    'user exist, medicament not exist, medicament is active - метод выбрасывает исключение' => [true, false, true],
    'user exist, medicament exist, medicament is not active - метод выбрасывает исключение' => [true, true, false],
    'user not exist, medicament not exist, medicament is active - метод выбрасывает исключение' => [false, false, true],
    'user not exist, medicament exist, medicament is not active - метод выбрасывает исключение' => [false, true, false],
    'user exist, medicament not exist, medicament is not active - метод выбрасывает исключение' => [true, false, false],
]);
