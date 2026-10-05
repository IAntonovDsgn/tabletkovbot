<?php

declare(strict_types=1);

namespace App\Application\Services\PdfFactory;

use App\Domain\Entities\Report\Report;

interface PdfFactoryInterface
{
    public function createFromReport(Report $report): string;
}
