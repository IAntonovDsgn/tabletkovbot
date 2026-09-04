<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\Exceptions\InvalidValueException;
use App\Application\BotManager\KeyboardFactory;
use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Report\Report;
use App\Domain\Entities\Session\Session;
use DateTimeImmutable;

final readonly class StateDownloadReportStartDateEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private IntakeMarkRepositoryInterface $intakeMarkRepository,
        private KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * @throws InvalidValueException
     */
    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        if ($messageText === null) {
            throw new InvalidValueException(EnumMessageText::FORMAT_DATE_ERROR->value);
        }

        $startDate = DateTimeImmutable::createFromFormat('!' . Report::DATE_FORMAT, $messageText);
        if ($startDate === false) {
            throw new InvalidValueException(EnumMessageText::FORMAT_DATE_ERROR->value);
        }

        $intakeMarks = $this->intakeMarkRepository->findByChatId($session->getChatId());
        if (empty($intakeMarks)) {
            $result = new StateHandlerResponseDTO(
                EnumMessageText::INTAKE_MARKS_NOT_FOUND,
                $this->keyboardFactory->makeMenuKeyboard()
            );
        } else {
            $report = new Report($startDate, $intakeMarks);
            $result = new StateHandlerResponseDTO(
                EnumMessageText::REPORT_READY,
                $this->keyboardFactory->makeMenuKeyboard(),
                report: $report
            );
        }

        return $result;
    }
}
