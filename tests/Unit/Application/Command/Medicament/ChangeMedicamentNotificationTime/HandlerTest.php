<?php

namespace Tests\Unit\Application\Command\ChangeMedicamentNotificationTime;

use App\Application\Command\Medicament\ChangeMedicamentNotificationTime\ChangeMedicamentNotificationTimeException;
use App\Application\Command\Medicament\ChangeMedicamentNotificationTime\Handler;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentFactory;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use Mockery;
use Throwable;

test('', function (string $newNotificationTime, bool $isMedicamentExist, bool $isMedicamentActive) {
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
        expect(fn() => $handler($medicamentId, $newNotificationTime))
            ->toThrow(ChangeMedicamentNotificationTimeException::class);
    } else {
        expect(fn() => $handler($medicamentId, $newNotificationTime))
            ->not->toThrow(Throwable::class);
    }

})->with('change-medicament-notification-time');

dataset('change-medicament-notification-time', [
    'medicament exist' => ["14:00:00", true, true],
    'medicament not exist' => ["14:00:00", false, false],
    'medicament exist, but not active' => ["14:00:00", true, false],
]);
