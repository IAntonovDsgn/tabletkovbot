<?php

use App\Application\Command\Medicament\DeactivateMedicament\DeactivateMedicamentException;
use App\Application\Command\Medicament\DeactivateMedicament\Handler;
use App\Domain\Medicament\MedicamentFactory;
use App\Domain\Medicament\MedicamentRepositoryInterface;

test('', function (bool $isMedicamentExist, bool $isMedicamentActive) {
    $medicamentId = 1;
    $medicament = null;

    if ($isMedicamentExist) {
        $medicament = MedicamentFactory::create('testMedicament', $medicamentId);
        if (! $isMedicamentActive) {
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
            ->with($medicament);
    }

    $handler = new Handler($medicamentRepositoryMock);

    if ($isMedicamentExist && $isMedicamentActive) {
        expect(fn() => $handler($medicamentId))
            ->not->toThrow(Throwable::class);
    } else {
        expect(fn() => $handler($medicamentId))
            ->toThrow(DeactivateMedicamentException::class);
    }

})->with('delete medicament');

dataset('delete medicament', [
    'medicament exist - метод не выбрасывает исключение' => [true, true],
    'medicament not exist - метод выбрасывает исключение' => [false, false],
    'medicament exist, but not active - метод выбрасывает исключение' => [true, false],
]);
