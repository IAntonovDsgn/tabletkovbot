<?php

namespace Tests\Unit\Presentation\Console\Commands;

use App\Application\NotificationService\NotificationService;
use App\Application\Outbox\OutboxRepositoryInterface;
use App\Application\UnitOfWork\UnitOfWorkInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Presentation\Console\Commands\MedicationNotifyCommand;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\FakeLogger;

class MedicationNotifyCommandTest extends TestCase
{
    public function testRunDispatchesDueMedicamentsUntilStopped(): void
    {
        $medicamentRepository = $this->createMock(MedicamentRepositoryInterface::class);
        $outboxRepository = $this->createMock(OutboxRepositoryInterface::class);
        $unitOfWork = $this->createMock(UnitOfWorkInterface::class);
        $logger = new FakeLogger();

        $service = new NotificationService(
            $medicamentRepository,
            $outboxRepository,
            $unitOfWork,
            $logger,
            10,
        );

        $medicament = Medicament::restoreFromPersistence(1, 'Aspirin', 77, new DateTimeImmutable(), true);
        $medicamentRepository->method('findForNotificationNow')->willReturn([$medicament]);

        $outboxRepository->expects($this->once())->method('insert');
        $medicamentRepository->expects($this->once())
            ->method('update')
            ->willReturnCallback(function () use ($service): void {
                $service->requestStop();
            });

        $command = new MedicationNotifyCommand($service);
        $tester = new CommandTester($command);
        $statusCode = $tester->execute([]);

        self::assertSame(0, $statusCode);
    }
}
