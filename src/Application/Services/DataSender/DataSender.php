<?php

declare(strict_types=1);

namespace App\Application\Services\DataSender;

use App\Application\Services\PdfFactory\PdfFactoryInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;

final readonly class DataSender
{
    public function __construct(
        private DataTransportInterface $dataTransport,
        private PdfFactoryInterface $pdfFactory,
    ) {}

    public function sendMessage(Message $message): void
    {
        $this->dataTransport->sendMessage($message);
    }

    public function sendReport(Report $report): void
    {
        $reportPdfPath = $this->pdfFactory->createFromReport($report);
        $this->dataTransport->sendFile($reportPdfPath, $report->getChatId());
        unlink($reportPdfPath);
    }
}
