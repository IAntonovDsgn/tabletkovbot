<?php

namespace Tests\Unit\Application\Command\CreateMedicament;

use App\Application\Command\CreateMedicament\CreateMedicamentException;
use App\Application\Command\CreateMedicament\Handler;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use Mockery;

test('', function (
    string $medicamentName,
    string $notificationTime,
    bool $isMedicamentExist
): void {
    $userId = 1;
    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);
    $medicamentRepositoryMock
        ->shouldReceive('findByUserIdAndMedicamentName')
        ->with($medicamentName, $userId)
        ->andReturn(
            $isMedicamentExist
                ? new Medicament($medicamentName, $userId)
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
    'medicament exist' => ['medicament1', "14:30:00", true],
    'medicament not exist' => ['medicament1', "14:30:00", false],
]);
