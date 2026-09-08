<?php

namespace Tests\Unit\Application\MedicationNotification;

use App\Application\MedicationNotificationService\Service;
use App\Application\Outbox\OutboxRepositoryInterface;
use App\Application\UnitOfWork\UnitOfWorkInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\Message;
use App\Domain\Entities\Session\State\EnumState;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\FakeLogger;

class MedicationNotificationServiceTest extends TestCase
{
    private MockObject $medicamentRepository;
    private MockObject $outboxRepository;
    private MockObject $unitOfWork;
    private FakeLogger $logger;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $this->outboxRepository = $this->createMock(OutboxRepositoryInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWorkInterface::class);
        $this->logger = new FakeLogger();
        $this->service = new Service(
            $this->medicamentRepository,
            $this->outboxRepository,
            $this->unitOfWork,
            $this->logger,
            1000,
        );
    }

    private function makeMedicament(int $id, int $chatId): Medicament
    {
        return Medicament::restoreFromPersistence($id, 'Aspirin', $chatId, new DateTimeImmutable(), true);
    }

    public function testProcessCycleDispatchesDueMedicamentToOutbox(): void
    {
        $medicament = $this->makeMedicament(7, 123);

        $this->medicamentRepository->expects($this->once())
            ->method('findForNotificationNow')
            ->willReturn([$medicament]);

        $this->unitOfWork->expects($this->once())->method('begin');
        $this->unitOfWork->expects($this->once())->method('commit');
        $this->unitOfWork->expects($this->never())->method('rollback');

        $this->outboxRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (Message $message): bool {
                return $message->getChatId() === 123
                    && $message->getText() === EnumMessageText::NOTIFICATION_REMINDER->value . ': "Aspirin"'
                    && count($message->getButtons()) === 1
                    && $message->getButtons()[0]->getNewState() === EnumState::NOTIFIED->value
                    && $message->getButtons()[0]->getAdditionalPayload() === '7';
            }));

        $this->medicamentRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Medicament $m): bool {
                $today = new DateTimeImmutable('now', new DateTimeZone(Medicament::DATE_TIME_ZONE))
                    ->format(Medicament::DATE_FORMAT);

                return $m->getLastNotificationDate() !== null
                    && $m->getLastNotificationDate()->format(Medicament::DATE_FORMAT) === $today;
            }));

        $this->service->processCycle();

        self::assertSame([], $this->logger->records);
    }

    public function testProcessCycleRollsBackAndContinuesOnOutboxFailure(): void
    {
        $first = $this->makeMedicament(1, 111);
        $second = $this->makeMedicament(2, 222);

        $this->medicamentRepository->method('findForNotificationNow')->willReturn([$first, $second]);

        $this->unitOfWork->expects($this->exactly(2))->method('begin');
        $this->unitOfWork->expects($this->once())->method('commit');
        $this->unitOfWork->expects($this->once())->method('rollback');

        $this->outboxRepository->expects($this->exactly(2))
            ->method('insert')
            ->willReturnCallback(function (Message $message): void {
                if ($message->getChatId() === 111) {
                    throw new RuntimeException('outbox is down');
                }
            });

        $this->medicamentRepository->expects($this->once())->method('update');

        $this->service->processCycle();

        self::assertTrue($this->logger->hasMessage('outbox is down'));
        self::assertTrue($this->logger->hasRecordWithContext('phase', 'medication_notification'));
        self::assertSame(1, $this->logger->records[0]['context']['medicament_id']);
        self::assertSame(111, $this->logger->records[0]['context']['chat_id']);
    }

    public function testProcessCycleStopsMidwayWhenStopIsRequested(): void
    {
        $first = $this->makeMedicament(1, 111);
        $second = $this->makeMedicament(2, 222);

        $this->medicamentRepository->method('findForNotificationNow')->willReturn([$first, $second]);

        $this->outboxRepository->expects($this->once())->method('insert');
        $this->medicamentRepository->expects($this->once())
            ->method('update')
            ->willReturnCallback(function (): void {
                $this->service->requestStop();
            });

        $this->service->processCycle();
    }
}
