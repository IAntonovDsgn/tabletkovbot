<?php

namespace Tests\Unit\Application\Command\GetMedicamentsByUserId;

use App\Application\Command\GetMedicamentsByUserId\Handler;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use Mockery;

test('', function (bool $isMedicamentExist, bool $isMedicamentActive) {
    $medicamentId = 1;
    $userId = 1;
    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);
    $medicamentRepositoryMock
        ->shouldReceive('findByUserId')
        ->with($userId)
        ->andReturn(
            $isMedicamentExist
                ? [new Medicament('medicamentName', 1, isActive: $isMedicamentActive, id: $medicamentId)]
                : []
        );

    $handler = new Handler($medicamentRepositoryMock);
    $result = $handler($userId);

    if (!$isMedicamentExist || !$isMedicamentActive) {
        expect(count($result))->toBe(0);
    } else {
        expect(count($result))->toBe(1);
    }

})->with('get medicament by user id');

dataset('get medicament by user id', [
    'Medicament exist' => [true, true],
    'Medicament not exist' => [false, true],
    'Medicament exist, but not active' => [true, false]
]);
