<?php

namespace App\Presentation\Console\Commands;

use App\Domain\Telegram\TelegramFacadeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GetTelegramUpdatesCommand extends Command
{
    public function __construct(private readonly TelegramFacadeInterface $telegramFacade)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:tg-bot-get-updates');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $response = $this->telegramFacade->getUpdates();
            $result = 'Updates: '.print_r($response, true);
            $output->writeln($result);
            return self::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln("<error>Failed: " . $e->getMessage() . "</error>");
            return self::FAILURE;
        }
    }
}
