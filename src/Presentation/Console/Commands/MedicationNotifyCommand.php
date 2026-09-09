<?php

declare(strict_types=1);

namespace App\Presentation\Console\Commands;

use App\Application\NotificationService\NotificationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function extension_loaded;
use function pcntl_async_signals;
use function pcntl_signal;

use const SIGINT;
use const SIGTERM;

class MedicationNotifyCommand extends Command
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:medication-notify');
        $this->setDescription('Dispatches due daily medicament reminders to the outbox until stopped');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->registerSignalHandlers();
        $this->notificationService->run();

        return self::SUCCESS;
    }

    private function registerSignalHandlers(): void
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->notificationService->requestStop();
        });
        pcntl_signal(SIGINT, function () {
            $this->notificationService->requestStop();
        });
    }
}
