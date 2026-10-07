<?php

namespace Tests\Unit\Presentation\Console\Commands;

use App\Application\Services\Notification\NotificationService;
use App\Application\Services\Outbox\MessageOutboxRepositoryInterface;
use App\Domain\Entities\IntakeMark\IntakeMarkRepositoryInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\UnitOfWorkInterface;
use App\Presentation\Console\Commands\NotifyCommand;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\FakeLogger;

class MedicationNotifyCommandTest extends TestCase
{
    public function testRunDispatchesDueMedicamentsUntilStopped(): void
    {
        $medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $intakeMarkRepository = $this->createMock(IntakeMarkRepositoryInterface::class);
        $outboxRepository = $this->createMock(MessageOutboxRepositoryInterface::class);
        $unitOfWork = $this->createMock(UnitOfWorkInterface::class);
        $logger = new FakeLogger();

        $service = new NotificationService(
            $medicamentRepository,
            $intakeMarkRepository,
            $outboxRepository,
            $unitOfWork,
            $logger,
            10,
        );

        $medicament = Medicament::restoreFromPersistence(1, 'Aspirin', 77, new DateTimeImmutable(), true);
        $medicamentRepository->method('findForNotificationNow')->willReturn([$medicament]);
        $intakeMarkRepository->method('existsForTodayByMedicamentId')->willReturn(false);

        $outboxRepository->expects($this->once())->method('insert');
        $medicamentRepository->expects($this->once())
            ->method('update')
            ->willReturnCallback(function () use ($service): void {
                $service->requestStop();
            });

        $command = new NotifyCommand($service);
        $tester = new CommandTester($command);
        $statusCode = $tester->execute([]);

        self::assertSame(0, $statusCode);
    }
}
