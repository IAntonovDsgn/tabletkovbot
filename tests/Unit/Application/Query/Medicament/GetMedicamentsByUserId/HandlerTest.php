<?php

namespace Tests\Unit\Application\Command\GetMedicamentsByChatId;

use App\Application\Query\Medicament\GetMedicamentsByChatId\Handler;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use Mockery;

test('', function (bool $isMedicamentExist, bool $isMedicamentActive) {
    $chatId = 1;
    $medicaments = [];

    if ($isMedicamentExist) {
        $medicament = new Medicament('testMedicament', $chatId);
        if (! $isMedicamentActive) {
            $medicament->deactivate();
        }
        $medicaments[] = $medicament;
    }

    $medicamentRepositoryMock = Mockery::mock(MedicamentRepositoryInterface::class);
    $medicamentRepositoryMock
        ->shouldReceive('findByChatId')
        ->with($chatId)
        ->andReturn($medicaments);

    $handler = new Handler($medicamentRepositoryMock);
    $result = $handler($chatId);

    if ($isMedicamentExist && $isMedicamentActive) {
        expect(count($result))->toBe(1);
    } else {
        expect(count($result))->toBe(0);
    }

})->with('get medicament by chat id');

dataset('get medicament by chat id', [
    'One medicament exist and active - получаем массив с 1 элементом' => [true, true],
    'Medicament not exist - получаем пустой массив' => [false, true],
    'Medicament exist, but not active - получаем пустой массив' => [true, false]
]);
