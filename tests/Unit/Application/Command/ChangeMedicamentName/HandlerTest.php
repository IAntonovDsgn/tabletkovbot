<?php

namespace App\Tests\Unit\Application\Command\ChangeMedicamentName;

use App\Application\Command\ChangeMedicamentName\ChangeMedicamentNameException;
use App\Application\Command\ChangeMedicamentName\Handler;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use Mockery;
use Throwable;

test('', function (string $newName, bool $isMedicamentExist, bool $isMedicamentActive) {
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
        expect(fn() => $handler($medicamentId, $newName))
            ->toThrow(ChangeMedicamentNameException::class);
    } else {
        expect(fn() => $handler($medicamentId, $newName))
            ->not->toThrow(Throwable::class);
    }

})->with('change medicament name');

dataset('change medicament name', [
    'medicament exist' => ['testName', true, true],
    'medicament not exist' => ['testName', false, false],
    'medicament exist, but not active' => ['testName', true, false],
]);
