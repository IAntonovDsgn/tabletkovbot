<?php

declare(strict_types=1);

namespace App\Domain\Entities\Report;

use App\Domain\Entities\IntakeMark\IntakeMark;
use DateTimeImmutable;

final readonly class Report
{
    const string DATE_FORMAT = 'd.m.Y';

    /**
     * @param IntakeMark[] $intakeMarks
     */
    public function __construct(
        private DateTimeImmutable $startDate,
        private array $intakeMarks,
    ) {
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    /**
     * @return IntakeMark[]
     */
    public function getIntakeMarks(): array
    {
        return $this->intakeMarks;
    }
}
