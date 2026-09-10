<?php

declare(strict_types=1);

namespace App\Application\StateManager\UseCases;

use App\Application\Services\OutboxService\MessageOutboxRepositoryInterface;
use App\Application\Services\OutboxService\ReportOutboxRepositoryInterface;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Report\Report;
use DateTimeImmutable;

final readonly class StateDownloadReportStartDateEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
        private ReportOutboxRepositoryInterface $reportRepository,
        private KeyboardFactory $keyboardFactory,
        private MessageOutboxRepositoryInterface $outboxRepository,
    ) {
    }

    /**
     * @throws InvalidValueException
     */
    public function handle(RequestDTO $params): void {
        if ($params->messageText === null) {
            throw new InvalidValueException(EnumMessageText::FORMAT_DATE_ERROR->value);
        }

        $startDate = DateTimeImmutable::createFromFormat('!' . Report::DATE_FORMAT, $params->messageText);
        if ($startDate === false) {
            throw new InvalidValueException(EnumMessageText::FORMAT_DATE_ERROR->value);
        }

        $intakeMarks = $this->intakeMarkRepository->findByChatId($params->chatId);

        if (empty($intakeMarks)) {
            $this->outboxRepository->insert(
                Message::create(
                    $params->chatId,
                    EnumMessageText::INTAKE_MARKS_NOT_FOUND->value,
                    $this->keyboardFactory->makeMenuKeyboard()
                )
            );
        } else {
            $report = Report::create($params->chatId, $startDate);
            $this->reportRepository->insert($report);
            $this->outboxRepository->insert(
                Message::create(
                    $params->chatId,
                    EnumMessageText::START_MAKING_REPORT->value,
                    $this->keyboardFactory->makeMenuKeyboard()
                )
            );
        }
    }
}
