<?php

use App\Application\Command\Medicament\DeactivateMedicament\DeactivateMedicamentException;
use App\Application\Command\Medicament\DeactivateMedicament\Handler;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentFactory;
use App\Domain\Medicament\MedicamentRepositoryInterface;

test('', function (bool $isMedicamentExist, bool $isMedicamentActive) {
    $medicamentId = 1;
    $medicament = null;

    if ($isMedicamentExist) {
        $medicament = MedicamentFactory::create('testMedicament', $medicamentId);
        if (!$isMedicamentActive) {
            $medicament->deactivate();
        }
    }

    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);
    $medicamentRepositoryMock
        ->shouldReceive('findById')
        ->with($medicamentId)
        ->andReturn($medicament);

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
