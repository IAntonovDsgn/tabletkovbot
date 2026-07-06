<?php

use App\Application\Command\DeactivateMedicament\DeactivateMedicamentException;
use App\Application\Command\DeactivateMedicament\Handler;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentRepositoryInterface;

test('', function (bool $isMedicamentExist, bool $isMedicamentActive) {
    $medicamentId = 1;
    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);
    $medicamentRepositoryMock
        ->shouldReceive('findById')
        ->with($medicamentId)
        ->andReturn(
            $isMedicamentExist
                ? new Medicament('medicamentName', 1, isActive: $isMedicamentActive, id: $medicamentId)
                : null
        );

    if ($isMedicamentExist) {
        $medicamentRepositoryMock
            ->shouldReceive('save')
            ->with(Mockery::type(Medicament::class));
    }

    $handler = new Handler($medicamentRepositoryMock);

    if (!$isMedicamentExist || !$isMedicamentActive) {
        expect(fn() => $handler($medicamentId))
            ->toThrow(DeactivateMedicamentException::class);
    } else {
        expect(fn() => $handler($medicamentId))
            ->not->toThrow(Throwable::class);
    }

})->with('delete medicament');

dataset('delete medicament', [
    'medicament exist' => [true, true],
    'medicament not exist' => [false, false],
    'medicament exist, but not active' => [true, false],
]);
