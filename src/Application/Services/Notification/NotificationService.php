<?php

declare(strict_types=1);

namespace App\Application\Services\Notification;

use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\States\EnumState;
use App\Domain\UnitOfWorkInterface;
use Exception;
use Psr\Log\LoggerInterface;

final class NotificationService
{
    private bool $stopRequested = false;

    public function __construct(
        private readonly MedicamentRepositoryInterface $medicamentRepository,
        private readonly IntakeMarkRepositoryInterface $intakeMarkRepository,
        private readonly MessageOutboxRepositoryInterface $outboxRepository,
        private readonly UnitOfWorkInterface $unitOfWork,
        private readonly LoggerInterface $logger,
        private readonly int $pollIntervalMs,
    ) {}

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function run(): void
    {
        while ($this->stopRequested === false) {
            $medicamentsForNotification = $this->medicamentRepository->findForNotificationNow();

            foreach ($medicamentsForNotification as $medicament) {
                if ($this->intakeMarkRepository->existsForTodayByMedicamentId($medicament->getId()) === false) {
                    $this->sendNotification($medicament);
                };
            }

            usleep($this->pollIntervalMs * 1000);
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }

    private function sendNotification(Medicament $medicament): void
    {
        try {
            $this->unitOfWork->begin();
            $this->outboxRepository->insert(
                Message::create(
                    $medicament->getChatId(),
                    EnumMessageText::NOTIFICATION_REMINDER->value . ': "' . $medicament->getName() . '"',
                    [
                        new MessageButton(
                            MessageButton::MAKE_INTAKE_MARK_BUTTON_TITLE,
                            EnumState::NOTIFIED,
                            (string) $medicament->getId(),
                        ),
                        new MessageButton(
                            MessageButton::MENU,
                            EnumState::MENU
                        ),
                    ]
                )
            );

            $medicament->markNotificationSent();
            $this->medicamentRepository->update($medicament);
            $this->unitOfWork->commit();
        } catch (Exception $e) {
            $this->unitOfWork->rollback();
            $this->logger->error($e, [
                'medicament_id' => $medicament->getId(),
                'chat_id' => $medicament->getChatId(),
                'phase' => 'medication_notification',
            ]);
        }
    }
}
