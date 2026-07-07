<?php

namespace App\Application\Query\IntakeMark\GetReportData;

use App\Domain\IntakeMark\IntakeMark;
use App\Domain\IntakeMark\IntakeMarkRepositoryInterface;
use DateTimeImmutable;

final readonly class Handler
{
    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
    ) {
    }

    /**
     * @return IntakeMark[]
     * @throws GetReportDataException
     */
    public function __invoke(int $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $intakeMarks = $this->intakeMarkRepository->findByUserId($userId);

        if (empty($intakeMarks)) {
            throw new GetReportDataException('Intake Marks not found');
        }

        foreach ($intakeMarks as $key => $intakeMark) {
            if (!$intakeMark->isIncludeInInterval($startDate, $endDate)) {
                unset($intakeMarks[$key]);
            }
        }

        return $intakeMarks;
    }
}
