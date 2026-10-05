<?php

declare(strict_types=1);

namespace App\Presentation\Console\Commands;

use App\Infrastructure\RabbitMq\QueueConsumerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Throwable;

use function extension_loaded;
use function pcntl_async_signals;
use function pcntl_signal;

use const SIGINT;
use const SIGTERM;

class ReportConsumeCommand extends Command
{
    public function __construct(
        private readonly QueueConsumerInterface $reportQueueConsumer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:report-consume');
        $this->setDescription('Consumes the report queue, renders PDFs and delivers them to Telegram.');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->registerSignalHandlers();
        $this->reportQueueConsumer->run();

        return self::SUCCESS;
    }

    private function registerSignalHandlers(): void
    {
        if (!extension_loaded('point')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->reportQueueConsumer->requestStop();
        });
        pcntl_signal(SIGINT, function () {
            $this->reportQueueConsumer->requestStop();
        });
    }
}
