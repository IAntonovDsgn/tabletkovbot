<?php

namespace App\Tests\Unit\Application\Query\Medicament\GetMedicamentsByUserId;

use App\Application\Query\IntakeMark\GetReportData\GetReportDataException;
use App\Application\Query\IntakeMark\GetReportData\Handler;
use App\Domain\IntakeMark\IntakeMarkFactory;
use App\Domain\IntakeMark\IntakeMarkRepositoryInterface;
use DateTimeImmutable;
use Mockery;

test('', function (bool $isUserExist, bool $isIntakeMarksForUserExist, DateTimeImmutable $createdAt) {
    $userId = 1;
    $medicamentId = 1;

    $intakeMarkRepositoryMock = Mockery::mock(IntakeMarkRepositoryInterface::class);
    $intakeMarkRepositoryMock
        ->shouldReceive('findByUserId')
        ->with($userId)
        ->andReturn($isIntakeMarksForUserExist
            ? [IntakeMarkFactory::restore($userId, $medicamentId, true, $createdAt, 1)]
            : []
        );

    $handler = new Handler($intakeMarkRepositoryMock);
    $startDate = DateTimeImmutable::createFromFormat("Y-m-d", "2026-01-01");
    $endDate = DateTimeImmutable::createFromFormat("Y-m-d", "2027-01-01");
    $isIncludeInInterval = $createdAt >= $startDate && $createdAt <= $endDate;

    if ($isUserExist && $isIntakeMarksForUserExist && $isIncludeInInterval) {
        $result = $handler($userId, $startDate, $endDate);
        expect($result)->toBeArray()
            ->and($result)->toHaveCount(1);
    } elseif ($isUserExist && $isIntakeMarksForUserExist) {
        $result = $handler($userId, $startDate, $endDate);
        expect($result)->toBeArray()
            ->and($result)->toBeEmpty();
    } else {
        expect(fn() => $handler($userId, $startDate, $endDate))
            ->toThrow(GetReportDataException::class);
    }

})->with('get report data');

dataset('get report data', [
    'user exist' => [true, true, new DateTimeImmutable("2026-02-01")],
    'user not exist' => [false, false, new DateTimeImmutable("2026-02-01")],
    'user exist, but Intake marks is not exist' => [true, false, new DateTimeImmutable("2026-02-01")],
    'user exist, but Intake marks is not in interval' => [true, true, new DateTimeImmutable("2021-02-01")],
]);
