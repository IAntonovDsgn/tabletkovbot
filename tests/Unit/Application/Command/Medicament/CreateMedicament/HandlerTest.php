<?php

namespace Tests\Unit\Application\Command\CreateMedicament;

use App\Application\Command\Medicament\CreateMedicament\CreateMedicamentException;
use App\Application\Command\Medicament\CreateMedicament\Handler;
use App\Domain\Medicament\MedicamentFactory;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use DateTimeImmutable;
use Mockery;
use Throwable;

test('', function (bool $isMedicamentExist): void {
    $medicamentName = 'medicament1';
    $userId = 1;
    $notificationTime = new DateTimeImmutable("14:30:00");
    $medicament = null;

    if ($isMedicamentExist) {
        $medicament = MedicamentFactory::create($medicamentName, $userId, $notificationTime);
    }

    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);
    $medicamentRepositoryMock
        ->shouldReceive('findByUserIdAndMedicamentName')
        ->with($medicamentName, $userId)
        ->andReturn($medicament);

    if (!$isMedicamentExist) {
        $medicamentRepositoryMock
            ->shouldReceive('save')
            ->with($medicament)
            ->andReturn();
    }

    $handler = new Handler($medicamentRepositoryMock);

    if ($isMedicamentExist) {
        expect(fn() => $handler($userId, $medicamentName, $notificationTime))
            ->toThrow(CreateMedicamentException::class);
    } else {
        expect(fn() => $handler($userId, $medicamentName, $notificationTime))
            ->not->toThrow(Throwable::class);
    }
})->with('create medicament');

dataset('create medicament', [
    'medicament exist - метод не выбрасывает исключение' => [true],
    'medicament not exist - метод выбрасывает исключение' => [false],
]);
