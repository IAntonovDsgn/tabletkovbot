<?php

namespace Tests\Unit\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlers\StateDownloadReportStartDateEnteredHandler;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Application\Services\Keyboard\KeyboardFactory;
use App\Domain\Entities\IntakeMark\IntakeMark;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Report\Report;
use App\Domain\Exceptions\External\InvalidValueException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StateDownloadReportStartDateEnteredHandlerTest extends TestCase
{
    private IntakeMarkRepositoryInterface $intakeMarkRepository;
    private KeyboardFactory $keyboardFactory;
    private StateDownloadReportStartDateEnteredHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->intakeMarkRepository = $this->createMock(IntakeMarkRepositoryInterface::class);
        $this->keyboardFactory = new KeyboardFactory();
        $this->handler = new StateDownloadReportStartDateEnteredHandler(
            $this->intakeMarkRepository,
            $this->keyboardFactory
        );
    }

    public function testHandleSuccessWithIntakeMarks(): void
    {
        $chatId = 12345;
        $date = '01.01.2023';
        $startDate = DateTimeImmutable::createFromFormat('!' . Report::DATE_FORMAT, $date);

        $intakeMarks = [
            new IntakeMark($chatId, 1, new DateTimeImmutable()),
        ];

        $this->intakeMarkRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn($intakeMarks);

        $response = $this->handler->handle($chatId, $date, null, null);

        $expectedReport = new Report($startDate, $intakeMarks);
        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::REPORT_READY,
            $this->keyboardFactory->makeMenuKeyboard(),
            report: $expectedReport
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleSuccessWithNoIntakeMarks(): void
    {
        $chatId = 12345;
        $date = '01.01.2023';

        $this->intakeMarkRepository->expects($this->once())
            ->method('findByChatId')
            ->with($chatId)
            ->willReturn([]);

        $response = $this->handler->handle($chatId, $date, null, null);

        $expectedResponse = new StateHandlerResponseDTO(
            EnumMessageText::INTAKE_MARKS_NOT_FOUND,
            $this->keyboardFactory->makeMenuKeyboard()
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleThrowsExceptionForInvalidDateFormat(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::FORMAT_DATE_ERROR->value);

        $this->handler->handle(12345, 'invalid-date', null, null);
    }

    public function testHandleThrowsExceptionForNullDate(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(EnumMessageText::FORMAT_DATE_ERROR->value);

        $this->handler->handle(12345, null, null, null);
    }
}
