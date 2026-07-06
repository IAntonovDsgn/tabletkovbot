<?php

namespace Tests\Unit\Application\Command\ChangeMedicamentNotificationTime;

use App\Application\Command\ChangeMedicamentNotificationTime\ChangeMedicamentNotificationTimeException;
use App\Application\Command\ChangeMedicamentNotificationTime\Handler;
use App\Domain\Medicament\Medicament;
use App\Domain\Medicament\MedicamentRepositoryInterface;
use Mockery;
use Throwable;

test('', function (string $newNotificationTime, bool $isMedicamentExist, bool $isMedicamentActive) {
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
