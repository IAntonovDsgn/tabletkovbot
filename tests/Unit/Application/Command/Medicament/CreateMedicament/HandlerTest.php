<?php

namespace Tests\Unit\Application\Command\CreateMedicament;

use App\Application\Command\Medicament\CreateMedicament\CreateMedicamentException;
use App\Application\Command\Medicament\CreateMedicament\Handler;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentFactory;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use DateTimeImmutable;
use Mockery;

test('', function (bool $isMedicamentExist): void {
    $medicamentName = 'medicament1';
    $userId = 1;
    $notificationTime = new DateTimeImmutable("14:30:00");

    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);
    $medicamentRepositoryMock
        ->shouldReceive('findByUserIdAndMedicamentName')
        ->with($medicamentName, $userId)
        ->andReturn(
            $isMedicamentExist
                ? MedicamentFactory::create($medicamentName, $userId, $notificationTime)
                : null
        );

    if (!$isMedicamentExist) {
        $medicamentRepositoryMock
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(Medicament::class));
    }

    $handler = new Handler($medicamentRepositoryMock);

    if ($isMedicamentExist) {
        expect(fn() => $handler($userId, $medicamentName, $notificationTime))
            ->toThrow(CreateMedicamentException::class);
    } else {
        $handler($userId, $medicamentName, $notificationTime);
    }
})->with('create medicament');

dataset('create medicament', [
    'medicament exist' => [true],
    'medicament not exist' => [false],
]);
