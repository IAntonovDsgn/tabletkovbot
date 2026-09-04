<?php

declare(strict_types=1);

namespace App\Presentation\Console\Commands;

use App\Application\Message\QueueConsumerInterface;
use App\Application\Message\SendMessage\Handler;
use App\Domain\Entities\Message\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function extension_loaded;
use function pcntl_async_signals;
use function pcntl_signal;

use const SIGINT;
use const SIGTERM;

class QueueConsumeCommand extends Command
{
    public function __construct(
        private readonly QueueConsumerInterface $consumer,
        private readonly Handler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:queue-consume');
        $this->setDescription('Consumes the telegram.send-message queue and delivers messages to Telegram.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->registerSignalHandlers();

        $this->consumer->run(function (Message $message): void {
            $this->handler->handle(
                $message->getChatId(),
                $message->getText(),
                $message->getButtons(),
            );
        });

        return self::SUCCESS;
    }

    private function registerSignalHandlers(): void
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->consumer->requestStop();
        });
        pcntl_signal(SIGINT, function () {
            $this->consumer->requestStop();
        });
    }
}
