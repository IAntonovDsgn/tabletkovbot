<?php

namespace Tests\Unit\Infrastructure\PDF;

use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Support\DateFormats;
use App\Domain\Entities\Report\Report;
use App\Infrastructure\Dbal\Exceptions\RepositoryException;
use App\Infrastructure\PDF\PdfFactory;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Mpdf\MpdfException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use Throwable;

class PdfFactoryTest extends TestCase
{
    private const int CHAT_ID = 4242;
    private const int REPORT_ID = 1;
    private const int MAX_DAYS = 365;
    private const int TEMP_FILE_TTL = 3600;

    private MockObject $intakeMarkRepository;
    private MockObject $medicamentRepository;
    private PdfFactory $factory;
    private string $tempDir;

    /**
     * @var string[]
     */
    private array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->intakeMarkRepository = $this->createMock(IntakeMarkRepositoryInterface::class);
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->factory = new PdfFactory(
            $this->intakeMarkRepository,
            $this->medicamentRepository,
            self::MAX_DAYS,
            self::TEMP_FILE_TTL,
        );
        $this->tempDir = dirname(__DIR__, 4) . '/storage/temp/';
    }

    protected function tearDown(): void
    {
        // PdfFactory picks a random file name per render, so the leftovers have to be collected
        // rather than reconstructed from the report id.
        foreach ($this->createdFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->createdFiles = [];

        parent::tearDown();
    }

    /**
     * @throws MpdfException
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    private function render(Report $report): string
    {
        $path = $this->factory->createFromReport($report);
        $this->createdFiles[] = $path;

        return $path;
    }

    /**
     * @throws DateMalformedStringException
     */
    private function currentMonthStart(): DateTimeImmutable
    {
        $now = new DateTimeImmutable('now', new DateTimeZone(DateFormats::TIME_ZONE));

        return $now->modify('first day of this month')->setTime(0, 0);
    }

    /**
     * @throws DateMalformedStringException
     */
    private function makeReport(): Report
    {
        return Report::restoreFromPersistence(
            self::REPORT_ID,
            self::CHAT_ID,
            $this->currentMonthStart(),
            0
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    private function makeMark(int $id, int $medicamentId, int $day): IntakeMark
    {
        $createdAt = $this->currentMonthStart()->modify('+' . ($day - 1) . ' days')->setTime(9, 0);

        return IntakeMark::restoreFromPersistence($id, self::CHAT_ID, $medicamentId, $createdAt, true);
    }

    /**
     * @param IntakeMark[] $marks
     * @throws DateMalformedStringException
     */
    private function stubCurrentMonthMarks(array $marks): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone(DateFormats::TIME_ZONE));
        $year = (int) $now->format('Y');
        $month = (int) $now->format('m');

        $this->intakeMarkRepository->method('findForMonthByChatId')
            ->willReturnCallback(
                static fn(int $chatId, int $requestedYear, int $requestedMonth): array
                    => ($requestedYear === $year && $requestedMonth === $month) ? $marks : []
            );
    }

    private function makeMedicament(int $id, string $name): Medicament
    {
        return Medicament::restoreFromPersistence($id, $name, self::CHAT_ID, null, true);
    }

    /**
     * @throws DateMalformedStringException
     * @throws MpdfException
     * @throws RandomException
     */
    public function testResolvesMedicamentNamesWithOneQueryInsteadOfOnePerRow(): void
    {
        $this->stubCurrentMonthMarks([
            $this->makeMark(1, 1, 1),
            $this->makeMark(2, 2, 2),
            $this->makeMark(3, 1, 3),
        ]);

        $this->medicamentRepository->expects($this->once())
            ->method('findByChatId')
            ->with(self::CHAT_ID)
            ->willReturn([
                $this->makeMedicament(1, 'Аспирин'),
                $this->makeMedicament(2, 'Ибупрофен'),
            ]);

        $this->medicamentRepository->expects($this->never())->method('findById');

        $this->assertFileExists($this->render($this->makeReport()));
    }

    /**
     * @throws DateMalformedStringException
     * @throws MpdfException
     * @throws RandomException
     */
    public function testQueryCountDoesNotGrowWithNumberOfMonths(): void
    {
        $this->stubCurrentMonthMarks([
            $this->makeMark(1, 1, 1),
            $this->makeMark(2, 2, 2),
        ]);

        $calls = 0;

        $this->medicamentRepository->method('findByChatId')
            ->willReturnCallback(function () use (&$calls): array {
                $calls++;

                return [
                    $this->makeMedicament(1, 'Аспирин'),
                    $this->makeMedicament(2, 'Ибупрофен'),
                ];
            });

        $this->medicamentRepository->expects($this->never())->method('findById');

        $this->render($this->makeReport());

        $this->assertSame(1, $calls, 'findByChatId() must be called exactly once per report');
    }

    /**
     * @throws DateMalformedStringException
     * @throws MpdfException
     * @throws RandomException
     */
    public function testSkipsMedicamentWithoutIdWhenBuildingNamesMap(): void
    {
        $this->stubCurrentMonthMarks([$this->makeMark(1, 7, 1)]);

        $this->medicamentRepository->method('findByChatId')->willReturn([
            Medicament::create('Без идентификатора', self::CHAT_ID),
            $this->makeMedicament(7, 'Норил'),
        ]);

        $this->medicamentRepository->expects($this->never())->method('findById');

        $this->assertFileExists($this->render($this->makeReport()));
    }

    /**
     * @throws DateMalformedStringException
     * @throws MpdfException
     * @throws RandomException
     */
    public function testSkipsIntakeMarksWhoseMedicamentWasDeleted(): void
    {
        $this->stubCurrentMonthMarks([
            $this->makeMark(1, 999, 1),
            $this->makeMark(2, 1, 2),
        ]);

        $this->medicamentRepository->method('findByChatId')->willReturn([
            $this->makeMedicament(1, 'Аспирин'),
        ]);

        $this->medicamentRepository->expects($this->never())->method('findById');

        $this->assertFileExists($this->render($this->makeReport()));
    }

    /**
     * @throws DateMalformedStringException
     * @throws MpdfException
     * @throws RandomException
     */
    public function testProducesNonEmptyPdfFile(): void
    {
        $this->stubCurrentMonthMarks([$this->makeMark(1, 1, 1)]);

        $this->medicamentRepository->method('findByChatId')->willReturn([
            $this->makeMedicament(1, 'Аспирин'),
        ]);

        $path = $this->render($this->makeReport());

        $this->assertFileExists($path);
        $this->assertStringStartsWith(
            $this->tempDir . self::REPORT_ID . '_',
            $path,
            'PDF must land in storage/temp under a name derived from the report id',
        );
        $this->assertMatchesRegularExpression(
            '/' . preg_quote(self::REPORT_ID, '/') . '_[0-9a-f]{16}\.pdf$/',
            basename($path),
            'the random suffix keeps concurrent renders from overwriting each other',
        );

        clearstatcache(true, $path);
        $this->assertGreaterThan(0, (int) filesize($path));
        $this->assertStringStartsWith('%PDF-', (string) file_get_contents($path, false, null, 0, 5));
    }

    /**
     * @throws DateMalformedStringException
     * @throws MpdfException
     * @throws RandomException
     */
    public function testReportWithNoIntakeMarksStillProducesPdf(): void
    {
        $this->stubCurrentMonthMarks([]);

        $this->medicamentRepository->method('findByChatId')->willReturn([
            $this->makeMedicament(1, 'Аспирин'),
        ]);

        $this->medicamentRepository->expects($this->never())->method('findById');

        $this->assertFileExists($this->render($this->makeReport()));
    }

    /**
     * @throws DateMalformedStringException
     * @throws MpdfException
     * @throws RandomException
     */
    public function testNamesWithHtmlSpecialCharsDoNotBreakGeneration(): void
    {
        $this->stubCurrentMonthMarks([$this->makeMark(1, 1, 1)]);

        $this->medicamentRepository->method('findByChatId')->willReturn([
            $this->makeMedicament(1, '<b>A&B</b> "quoted" \'single\''),
        ]);

        $path = $this->render($this->makeReport());

        clearstatcache(true, $path);
        $this->assertGreaterThan(0, (int) filesize($path));
    }

    /**
     * @throws DateMalformedStringException
     * @throws MpdfException
     * @throws Throwable
     * @throws RandomException
     */
    public function testPropagatesRepositoryFailure(): void
    {
        $this->stubCurrentMonthMarks([$this->makeMark(1, 1, 1)]);

        $this->medicamentRepository->method('findByChatId')
            ->willThrowException(new RepositoryException('db is down'));

        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('db is down');

        try {
            $this->render($this->makeReport());
        } catch (Throwable $e) {
            self::assertSame([], $this->createdFiles, 'nothing must be written when the render fails');
            throw $e;
        }
    }
}
