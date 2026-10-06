<?php

declare(strict_types=1);

namespace App\Infrastructure\PDF;

use App\Application\Services\PdfFactory\PdfFactoryInterface;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Report\Report;
use App\Domain\Support\DateFormats;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use Random\RandomException;

final readonly class PdfFactory implements PdfFactoryInterface
{
    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
        private MedicamentRepositoryInterface $medicamentRepository,
        private int $maxDays,
        private int $tempFileTtl,
    ) {}

    /**
     * @throws MpdfException
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    public function createFromReport(Report $report): string
    {
        $startDate = $this->limitStartDate($report->getStartDate());

        $mpdf = new Mpdf([
            'default_font' => 'dejavusans',
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $css = '
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10px; }
            td { border: 1px solid #000; padding: 4px; text-align: center; }
            td.name-col { text-align: left; width: 25%; font-weight: bold; }
            h3 { margin-bottom: 8px; font-size: 14px; }
        ';

        $mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
        $generator = $this->getMonthlyDataGenerator($report->getChatId(), $startDate);
        $medicamentNames = $this->buildMedicamentNamesMap($report->getChatId());

        foreach ($generator as $yearMonth => $monthData) {
            $startDay = $monthData['start_day'];
            $endDay = $monthData['end_day'];
            $medicaments = $monthData['data'];

            $html = '<h3>Месяц: ' . $yearMonth . '</h3>';
            $html .= '<table>';
            $html .= '<th>';
            $html .= '<td style="background-color: lightgrey"><br></td>';
            for ($day = $startDay; $day <= $endDay; $day++) {
                $html .= '<td>' . $yearMonth . '-' . $day . '</td>';
            }
            $html .= '</th>';

            foreach ($medicaments as $medicamentId => $daysWithRecords) {
                $medicamentName = $medicamentNames[$medicamentId] ?? null;

                if ($medicamentName === null) {
                    continue;
                }

                $html .= '<tr>';
                $html .= '<td class="name-col">' . htmlspecialchars($medicamentName) . '</td>';

                for ($day = $startDay; $day <= $endDay; $day++) {
                    isset($daysWithRecords[$day]) ?
                        $html .= '<td style="background-color: lightgreen">ДА</td>' :
                        $html .= '<td style="background-color: indianred">НЕТ</td>';
                }

                $html .= '</tr>';
            }

            $html .= '</table>';

            $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
        }

        $tempDir = $this->tempDir();

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $this->purgeStaleTempFiles($tempDir);

        $savePath = $tempDir . sprintf('%s_%s.pdf', $report->getId() ?? 'new', bin2hex(random_bytes(8)));
        $mpdf->Output($savePath, Destination::FILE);

        return $savePath;
    }

    private function tempDir(): string
    {
        return dirname(__DIR__, 3) . '/storage/temp/';
    }

    private function purgeStaleTempFiles(string $tempDir): void
    {
        $cutoff = time() - $this->tempFileTtl;

        foreach (glob($tempDir . '*.pdf') ?: [] as $path) {
            $modifiedAt = filemtime($path);

            if ($modifiedAt !== false && $modifiedAt < $cutoff) {
                unlink($path);
            }
        }
    }

    /**
     * @return string[]
     */
    private function buildMedicamentNamesMap(int $chatId): array
    {
        $names = [];

        foreach ($this->medicamentRepository->findByChatId($chatId) as $medicament) {
            $medicamentId = $medicament->getId();

            if ($medicamentId === null) {
                continue;
            }

            $names[$medicamentId] = $medicament->getName();
        }

        return $names;
    }

    /**
     * @throws DateMalformedStringException
     */
    private function limitStartDate(DateTimeImmutable $startDate): DateTimeImmutable
    {
        $now = new DateTimeImmutable('now', new DateTimeZone(DateFormats::TIME_ZONE));
        $minStartDate = $now->modify('-' . $this->maxDays . ' days');

        if ($startDate < $minStartDate) {
            return $minStartDate;
        }

        if ($startDate > $now) {
            return $now;
        }

        return $startDate;
    }

    /**
     * @throws DateMalformedStringException
     */
    private function getMonthlyDataGenerator(int $chatId, DateTimeImmutable $startDate): Generator
    {
        $currentDate = clone $startDate;
        $endDate = new DateTimeImmutable('now', new DateTimeZone(DateFormats::TIME_ZONE));

        while ($currentDate->format(DateFormats::YEAR_MONTH) <= $endDate->format(DateFormats::YEAR_MONTH)) {
            $year = (int)$currentDate->format('Y');
            $month = (int)$currentDate->format('m');
            $yearMonth = $currentDate->format(DateFormats::YEAR_MONTH);

            $intakeMarks = $this->intakeMarkRepository->findForMonthByChatId($chatId, $year, $month);

            if (!empty($intakeMarks)) {
                $groupedMedicaments = [];
                foreach ($intakeMarks as $intakeMark) {
                    $day = (int)$intakeMark->getCreatedAt()->format('d');
                    $groupedMedicaments[$intakeMark->getMedicamentId()][$day] = true;
                }

                $startDay = 1;
                $endDay = (int)$currentDate->format('t');

                if ($yearMonth === $startDate->format(DateFormats::YEAR_MONTH)) {
                    $startDay = (int)$startDate->format('d');
                }

                if ($yearMonth === $endDate->format(DateFormats::YEAR_MONTH)) {
                    $endDay = (int)$endDate->format('d');
                }

                yield $yearMonth => [
                    'start_day' => $startDay,
                    'end_day' => $endDay,
                    'data' => $groupedMedicaments,
                ];
            }

            $currentDate = $currentDate->modify('first day of next month')->setTime(0, 0);
        }
    }
}
