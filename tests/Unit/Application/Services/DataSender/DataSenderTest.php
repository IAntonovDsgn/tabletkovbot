<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Services\DataSender;

use App\Application\Services\DataSender\DataSender;
use App\Application\Services\DataSender\DataTransportInterface;
use App\Application\Services\PdfFactory\PdfFactoryInterface;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;
use App\Infrastructure\TelegramDataTransport\SendMessageException;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataSenderTest extends TestCase
{
    private const int CHAT_ID = 42;

    private MockObject $dataTransport;
    private MockObject $pdfFactory;
    private DataSender $dataSender;
    private string $createdPdf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createdPdf = tempnam(sys_get_temp_dir(), 'TableTools_sender_');
        self::assertIsString($this->createdPdf);

        $this->dataTransport = $this->createMock(DataTransportInterface::class);
        $this->pdfFactory = $this->createMock(PdfFactoryInterface::class);
        $this->dataSender = new DataSender($this->dataTransport, $this->pdfFactory);
    }

    protected function tearDown(): void
    {
        if (is_file($this->createdPdf)) {
            unlink($this->createdPdf);
        }

        parent::tearDown();
    }

    /**
     * @throws DateMalformedStringException
     */
    private function makeReport(): Report
    {
        $startDate = new DateTimeImmutable('now', new DateTimeZone('UTC'))->modify('-7 days');

        return Report::restoreFromPersistence(7, self::CHAT_ID, $startDate, 0);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testSendReportRendersSendsAndRemovesThePdf(): void
    {
        $report = $this->makeReport();

        $this->pdfFactory->expects($this->once())
            ->method('createFromReport')
            ->with($report)
            ->willReturn($this->createdPdf);

        $this->dataTransport->expects($this->once())
            ->method('sendFile')
            ->with($this->createdPdf, self::CHAT_ID);

        $this->dataSender->sendReport($report);

        $this->assertFileDoesNotExist($this->createdPdf, 'PDF must be removed after a successful delivery');
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testSendReportKeepsThePdfWhenDeliveryFails(): void
    {
        $report = $this->makeReport();

        $this->pdfFactory->method('createFromReport')->willReturn($this->createdPdf);

        $this->dataTransport->expects($this->once())
            ->method('sendFile')
            ->willThrowException(new SendMessageException('chat not found'));

        $this->expectException(SendMessageException::class);

        try {
            $this->dataSender->sendReport($report);
        } finally {
            // Unlinking here would destroy the only copy before the queue retry re-rendered it;
            // the leftover file is swept by PdfFactory::purgeStaleTempFiles() instead.
            $this->assertFileExists($this->createdPdf, 'PDF must survive a failed delivery');
        }
    }

    public function testSendMessageDelegatesToTransport(): void
    {
        $message = Message::create(self::CHAT_ID, 'Привет');

        $this->dataTransport->expects($this->once())
            ->method('sendMessage')
            ->with($message);

        $this->dataSender->sendMessage($message);
    }
}
