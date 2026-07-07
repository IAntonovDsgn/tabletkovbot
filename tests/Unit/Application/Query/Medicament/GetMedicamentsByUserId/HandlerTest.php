<?php

namespace Tests\Unit\Application\Command\GetMedicamentsByUserId;

use App\Application\Query\Medicament\GetMedicamentsByUserId\Handler;
use App\Domain\Medicament\MedicamentFactory;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use Mockery;

test('', function (bool $isMedicamentExist, bool $isMedicamentActive) {
    $userId = 1;
    $medicaments = [];

    if ($isMedicamentExist) {
        $medicament = MedicamentFactory::create('testMedicament', $userId);
        if (! $isMedicamentActive) {
            $medicament->deactivate();
        }
        $medicaments[] = $medicament;
    }

    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);
    $medicamentRepositoryMock
        ->shouldReceive('findByUserId')
        ->with($userId)
        ->andReturn($medicaments);

    $handler = new Handler($medicamentRepositoryMock);
    $result = $handler($userId);

    if ($isMedicamentExist && $isMedicamentActive) {
        expect(count($result))->toBe(1);
    } else {
        expect(count($result))->toBe(0);
    }

})->with('get medicament by user id');

dataset('get medicament by user id', [
    'One medicament exist and active - получаем массив с 1 элементом' => [true, true],
    'Medicament not exist - получаем пустой массив' => [false, true],
    'Medicament exist, but not active - получаем пустой массив' => [true, false]
]);
