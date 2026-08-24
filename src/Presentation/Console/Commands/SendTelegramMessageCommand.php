<?php

declare(strict_types=1);

namespace App\Presentation\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class SendTelegramMessageCommand extends Command
{
    public function __construct(
        private readonly \App\Application\Message\SendMessage\Handler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:send-telegram-message');
        $this->addArgument('chat_id', InputArgument::REQUIRED);
        $this->addArgument('message', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $chatId = $input->getArgument('chat_id');
        if (!is_string($chatId) || !is_numeric($chatId)) {
            $output->writeln('<error>chat_id must be numeric</error>');
            return self::FAILURE;
        }

        $message = $input->getArgument('message');
        if (!is_string($message)) {
            $output->writeln('<error>message must be a string</error>');
            return self::FAILURE;
        }

        try {
            $this->handler->handle($chatId, $message);
            return self::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln("<error>Failed to send message: " . $e->getMessage() . "</error>");
            return self::FAILURE;
        }
    }
}
