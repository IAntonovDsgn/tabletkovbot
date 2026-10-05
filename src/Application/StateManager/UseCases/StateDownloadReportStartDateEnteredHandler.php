<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\Services\Outbox\ReportOutboxRepositoryInterface;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Report\Report;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Exception;

final readonly class StateDownloadReportStartDateEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
        private ReportOutboxRepositoryInterface $reportRepository,
        private KeyboardFactory $keyboardFactory,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {}

    /**
     * @throws InvalidValueException
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function handle(RequestDTO $params): void {
        $startDate = $this->parseStartDate($params->messageText);

        if (! $this->intakeMarkRepository->existsByChatId($params->chatId)) {
            $this->outboxRepository->insert(
                Message::create(
                    $params->chatId,
                    EnumMessageText::INTAKE_MARKS_NOT_FOUND->value,
                    $this->keyboardFactory->makeMenuKeyboard()
                )
            );

            return;
        }

        $this->reportRepository->insert(Report::create($params->chatId, $startDate));
        $this->outboxRepository->insert(
            Message::create(
                $params->chatId,
                EnumMessageText::START_MAKING_REPORT->value,
                $this->keyboardFactory->makeMenuKeyboard()
            )
        );
    }

    /**
     * @throws InvalidValueException
     * @throws DateMalformedStringException
     */
    private function parseStartDate(?string $raw): DateTimeImmutable
    {
        if ($raw === null) {
            throw new InvalidValueException(EnumMessageText::FORMAT_DATE_ERROR->value);
        }

        $startDate = DateTimeImmutable::createFromFormat('!' . Report::DATE_FORMAT, $raw);

        if ($startDate === false || $startDate->format(Report::DATE_FORMAT) !== $raw) {
            throw new InvalidValueException(EnumMessageText::FORMAT_DATE_ERROR->value);
        }

        $now = new DateTimeImmutable('now', new DateTimeZone(Medicament::DATE_TIME_ZONE));

        if ($startDate > $now) {
            throw new InvalidValueException(EnumMessageText::DATE_IN_THE_FUTURE_ERROR->value);
        }

        return $startDate;
    }
}
