<?php

namespace Unit\Application\Command\IntakeMark\DeactivateIntakeMark;

use App\Application\Command\IntakeMark\DeactivateIntakeMark\DeactivateIntakeMarkException;
use App\Application\Command\IntakeMark\DeactivateIntakeMark\Handler;
use App\Domain\IntakeMark\IntakeMarkFactory;
use App\Domain\IntakeMark\IntakeMarkRepositoryInterface;
use Mockery;
use Throwable;

test('', function ($isIntakeMarkExist, $isIntakeMarkActive) {
    $intakeMarkId = 1;
    $userId = 1;
    $medicamentId = 1;

    if ($isIntakeMarkExist) {
        $intakeMark = IntakeMarkFactory::create($userId, $medicamentId);
        if (!$isIntakeMarkActive) {
            $intakeMark->deactivate();
        }
    } else {
        $intakeMark = null;
    }

    $intakeMarkRepositoryMock = Mockery::mock(IntakeMarkRepositoryInterface::class);
    $intakeMarkRepositoryMock
        ->shouldReceive('findById')
        ->with($intakeMarkId)
        ->andReturn($intakeMark);

    $handler = new Handler($intakeMarkRepositoryMock);

    if ($isIntakeMarkActive && $isIntakeMarkExist) {
        expect(fn() => $handler($userId))
            ->not->toThrow(Throwable::class);
    } else {
        expect(fn() => $handler($userId))
            ->toThrow(DeactivateIntakeMarkException::class);
    }

})->with('deactivate intake mark');

dataset('deactivate intake mark', [
    'intake mark exist and active' => [true, true],
    'intake mark not exist' => [false, false],
    'intake mark exist, but not active' => [true, false],
]);
