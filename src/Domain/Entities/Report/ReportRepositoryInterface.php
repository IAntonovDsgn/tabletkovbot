<?php

declare(strict_types=1);

namespace App\Domain\Entities\Report;

interface ReportRepositoryInterface
{
    public function insert(Report $report): int;
}
