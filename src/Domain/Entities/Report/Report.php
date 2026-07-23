<?php

namespace App\Domain\Entities\Report;

use App\Domain\Entities\IntakeMark\IntakeMark;
use DateTimeImmutable;

final readonly class Report
{
    private string $startDate;
    const string DATE_FORMAT = 'dd.mm.yyyy';

    /**
     * @param IntakeMark[] $intakeMarks
     */
    public function __construct(
        DateTimeImmutable $startDate,
        private array $intakeMarks,
    ) {
        $this->startDate = $startDate->format(self::DATE_FORMAT);
    }

    public function getStartDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $this->startDate);
    }

    /**
     * @return IntakeMark[]
     */
    public function getIntakeMarks(): array
    {
        return $this->intakeMarks;
    }
}
