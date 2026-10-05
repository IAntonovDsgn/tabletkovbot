<?php

declare(strict_types=1);

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Application\Services\Outbox\ReportOutboxRepositoryInterface;
use App\Application\StateManager\Exceptions\InvalidValueException;
use App\Application\StateManager\Factories\KeyboardFactory;
use App\Application\StateManager\RequestDTO;
use App\Application\StateManager\UseCases\StateDownloadReportStartDateEnteredHandler;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Report\Report;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateDownloadReportStartDateEnteredHandlerTest extends TestCase
{
    private const int CHAT_ID = 12345;

    private MockObject $intakeMarkRepository;
    private MockObject $reportRepository;
    private MockObject $outboxRepository;
    private StateDownloadReportStartDateEnteredHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->intakeMarkRepository = $this->createMock(IntakeMarkRepositoryInterface::class);
        $this->reportRepository = $this->createMock(ReportOutboxRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $keyboardFactory = new KeyboardFactory();
        $this->handler = new StateDownloadReportStartDateEnteredHandler(
            $this->intakeMarkRepository,
            $this->reportRepository,
            $keyboardFactory,
            $this->outboxRepository,
        );
    }

    /**
     * @throws InvalidValueException
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testQueuesTheReportAndConfirmsItWhenMarksExist(): void
    {
        $this->intakeMarkRepository->expects($this->once())
            ->method('existsByChatId')
            ->with(self::CHAT_ID)
            ->willReturn(true);

        $this->reportRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn(Report $report): bool
                    => $report->getChatId() === self::CHAT_ID
                    && $report->getStartDate()->format(Report::DATE_FORMAT) === '01.01.2023'
            ));

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn($message): bool => $message->getText() === EnumMessageText::START_MAKING_REPORT->value
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID, '01.01.2023'));
    }

    /**
     * @throws InvalidValueException
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testTellsTheUserWhenNoMarksExist(): void
    {
        $this->intakeMarkRepository->expects($this->once())
            ->method('existsByChatId')
            ->with(self::CHAT_ID)
            ->willReturn(false);

        $this->reportRepository->expects($this->never())->method('insert');

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(
                fn($message): bool => $message->getText() === EnumMessageText::INTAKE_MARKS_NOT_FOUND->value
            ));

        $this->handler->handle(new RequestDTO(self::CHAT_ID, '01.01.2023'));
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testRejectsUnparsableDate(): void
    {
        $this->intakeMarkRepository->expects($this->never())->method('existsByChatId');

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::FORMAT_DATE_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID, 'not-a-date'));
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testRejectsNullDate(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::FORMAT_DATE_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID));
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testRejectsCalendarDateThatDoesNotExist(): void
    {
        $this->intakeMarkRepository->expects($this->never())->method('existsByChatId');

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::FORMAT_DATE_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID, '31.02.2026'));
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testRejectsTrailingGarbageAfterTheDate(): void
    {
        $this->intakeMarkRepository->expects($this->never())->method('existsByChatId');

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::FORMAT_DATE_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID, '01.01.2023 10:00'));
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testRejectsStartDateInTheFuture(): void
    {
        $this->intakeMarkRepository->expects($this->never())->method('existsByChatId');

        $tomorrow = new DateTimeImmutable('now')
            ->modify('+1 day')
            ->format(Report::DATE_FORMAT);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::DATE_IN_THE_FUTURE_ERROR->value);

        $this->handler->handle(new RequestDTO(self::CHAT_ID, $tomorrow));
    }

    /**
     * @throws DateMalformedStringException
     * @throws InvalidValueException
     * @throws Exception
     */
    public function testAcceptsTodayAsStartDate(): void
    {
        $today = new DateTimeImmutable('now')->format(Report::DATE_FORMAT);

        $this->intakeMarkRepository->method('existsByChatId')->willReturn(true);
        $this->reportRepository->expects($this->once())->method('insert');
        $this->outboxRepository->expects($this->once())->method('insert');

        $this->handler->handle(new RequestDTO(self::CHAT_ID, $today));
    }

    /**
     * @throws InvalidValueException
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testDoesNotLoadTheWholeMarkHistory(): void
    {
        $this->intakeMarkRepository->expects($this->never())->method('findByChatId');
        $this->intakeMarkRepository->method('existsByChatId')->willReturn(true);
        $this->reportRepository->method('insert');
        $this->outboxRepository->method('insert');

        $this->handler->handle(new RequestDTO(self::CHAT_ID, '01.01.2023'));
    }
}
