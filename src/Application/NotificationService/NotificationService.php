<?php

declare(strict_types=1);

namespace App\Application\NotificationService;

use App\Application\Outbox\OutboxRepositoryInterface;
use App\Application\UnitOfWork\UnitOfWorkInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\State\EnumState;
use Exception;
use Psr\Log\LoggerInterface;

final class NotificationService
{
    private bool $stopRequested = false;

    public function __construct(
        private readonly MedicamentRepositoryInterface $medicamentRepository,
        private readonly OutboxRepositoryInterface $outboxRepository,
        private readonly UnitOfWorkInterface $unitOfWork,
        private readonly LoggerInterface $logger,
        private readonly int $pollIntervalMs,
    ) {}

    public function run(): void
    {
        while (!$this->stopRequested) {
            try {
                $this->processCycle();
                usleep($this->pollIntervalMs * 1000);
            } catch (Exception $e) {
                $this->logger->error($e, ['phase' => 'cycle']);
                usleep($this->pollIntervalMs * 1000);
            }
        }
    }

    public function processCycle(): void
    {
        $medicamentsForNotification = $this->medicamentRepository->findForNotificationNow();

        foreach ($medicamentsForNotification as $medicament) {
            if ($this->stopRequested) {
                break;
            }

            try {
                $this->dispatch($medicament);
            } catch (Exception $e) {
                $this->logger->error($e, [
                    'medicament_id' => $medicament->getId(),
                    'chat_id' => $medicament->getChatId(),
                    'phase' => 'medication_notification',
                ]);
            }
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }

    /**
     * @throws Exception
     */
    private function dispatch(Medicament $medicament): void
    {
        $this->unitOfWork->begin();
        try {
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
                    ]
                )
            );

            $medicament->markNotificationSent();
            $this->medicamentRepository->update($medicament);
            $this->unitOfWork->commit();
        } catch (Exception $e) {
            $this->unitOfWork->rollback();
            throw $e;
        }
    }
}
