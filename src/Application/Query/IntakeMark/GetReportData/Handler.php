<?php

namespace App\Application\Query\IntakeMark\GetReportData;

use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use DateTimeImmutable;

final readonly class Handler
{
    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
    ) {
    }

    /**
     * @return IntakeMark[]
     */
    public function __invoke(int $chatId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $intakeMarks = $this->intakeMarkRepository->findByChatId($chatId);

        foreach ($intakeMarks as $key => $intakeMark) {
            if (! $intakeMark->isIncludeInInterval($startDate, $endDate)) {
                unset($intakeMarks[$key]);
            }
        }

        return $intakeMarks;
    }
}
