<?php

namespace App\Presentation\console\Commands;

use App\Infrastructure\Services\Telegram\TelegramFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GetTelegramUpdatesCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('app:tg-bot-get-updates');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $response = TelegramFacade::getUpdates();
            $result = 'Updates: '.print_r($response, true);
            $output->writeln($result);
            return self::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln("<error>Failed: " . $e->getMessage() . "</error>");
            return self::FAILURE;
        }
    }
}
