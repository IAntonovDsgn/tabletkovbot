<?php

namespace App\Tests\Unit\Application\Query\Medicament\GetMedicamentsByChatId;

use App\Application\Query\IntakeMark\GetReportData\Handler;
use App\Domain\IntakeMark\IntakeMark;
use App\Domain\IntakeMark\IntakeMarkRepositoryInterface;
use DateTimeImmutable;
use Mockery;

test('', function (bool $isIntakeMarksForChatExist, DateTimeImmutable $createdAt) {
    $chatId = 1;
    $medicamentId = 1;
    $intakeMarks = [];

    if ($isIntakeMarksForChatExist) {
        $intakeMark = new IntakeMark($chatId, $medicamentId, $createdAt, true, 1);
        $intakeMarks[] = $intakeMark;
    }

    $intakeMarkRepositoryMock = Mockery::mock(IntakeMarkRepositoryInterface::class);
    $intakeMarkRepositoryMock
        ->shouldReceive('findByChatId')
        ->with($chatId)
        ->andReturn($intakeMarks);

    $handler = new Handler($intakeMarkRepositoryMock);
    $startDate = DateTimeImmutable::createFromFormat("Y-m-d", "2026-01-01");
    $endDate = DateTimeImmutable::createFromFormat("Y-m-d", "2027-01-01");
    $isIncludeInInterval = $createdAt >= $startDate && $createdAt <= $endDate;

    if ($isIntakeMarksForChatExist && $isIncludeInInterval) {
        $result = $handler($chatId, $startDate, $endDate);
        expect($result)->toBeArray()
            ->and($result)->toHaveCount(1);
    } else {
        $result = $handler($chatId, $startDate, $endDate);
        expect($result)->toBeArray()
            ->and($result)->toBeEmpty();
    }

})->with('get report data');

dataset('get report data', [
    'One intake mark exist - получаем массив с одним элементом' => [true, new DateTimeImmutable("2026-02-01")],
    'Intake marks is not exist - получаем пустой массив' => [false, new DateTimeImmutable("2026-02-01")],
    'Intake mark exist, but is not in interval - получаем пустой массив' => [true, new DateTimeImmutable("2021-02-01")],
]);
