<?php

declare(strict_types=1);

namespace App\Application\Services\Outbox;

use App\Domain\Entities\Report\Report;

interface ReportOutboxRepositoryInterface
{
    public function insert(Report $report): int;

    public function update(Report $report): void;

    public function markAttempt(Report $report): int;

    public function delete(Report $report): void;

    /**
     * @return Report[]
     */
    public function getReports(int $limit): array;
}
