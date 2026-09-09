<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Dbal\Repositories;

use App\Domain\Entities\Report\Report;
use App\Domain\Entities\Report\ReportRepositoryInterface;

class ReportRepository implements ReportRepositoryInterface
{
    const string REPORT_TABLE_NAME = 'reports';
    const string ID_COLUMN_NAME = 'id';
    const string CHAT_ID_COLUMN_NAME = 'chat_id';
    const string START_DATE_COLUMN_NAME = 'start_date';
    const string CREATED_AT_COLUMN_NAME = 'created_at';

    public function insert(Report $report): int
    {
        // TODO: Implement insert() method.
    }
}
