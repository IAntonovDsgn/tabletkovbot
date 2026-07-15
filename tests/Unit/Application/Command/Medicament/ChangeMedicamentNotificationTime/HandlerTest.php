<?php

namespace Tests\Unit\Application\Command\ChangeMedicamentNotificationTime;

use App\Application\Command\Medicament\ChangeMedicamentNotificationTime\ChangeMedicamentNotificationTimeException;
use App\Application\Command\Medicament\ChangeMedicamentNotificationTime\Handler;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use Mockery;
use Throwable;

test('', function (bool $isMedicamentExist, bool $isMedicamentActive) {
    $medicamentId = 1;
    $medicament = null;
    $newNotificationTime = "14:00:00";

    if ($isMedicamentExist) {
        $medicament = new Medicament('testMedicament', 1, id: $medicamentId);
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
            ->with(Mockery::type(Medicament::class));
    }

    $handler = new Handler($medicamentRepositoryMock);

    if ($isMedicamentExist && $isMedicamentActive) {
        expect(fn() => $handler($medicamentId, $newNotificationTime))
            ->not->toThrow(Throwable::class);
    } else {
        expect(fn() => $handler($medicamentId, $newNotificationTime))
            ->toThrow(ChangeMedicamentNotificationTimeException::class);
    }

})->with('change-medicament-notification-time');

dataset('change-medicament-notification-time', [
    'medicament exist and active - метод не выбрасывает исключение' => [true, true],
    'medicament not exist - метод выбрасывает исключение' => [false, false],
    'medicament exist, but not active - метод выбрасывает исключение' => [true, false],
]);
