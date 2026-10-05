<?php

declare(strict_types=1);

namespace App\Presentation\Console\Commands;

use App\Application\Services\DataSender\DataTransportInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GetTelegramUpdatesCommand extends Command
{
    public function __construct(
        private readonly DataTransportInterface $messageService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:tg-bot-get-updates');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $updates = $this->messageService->getUpdates();

            if (empty($updates)) {
                $output->writeln('No updates');
                return self::SUCCESS;
            }

            foreach ($updates as $update) {
                $output->writeln(sprintf('chat_id: %d, text: %s', $update->chatId, $update->messageText ?? ''));
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln("<error>Failed: " . $e->getMessage() . "</error>");
            return self::FAILURE;
        }
    }
}
