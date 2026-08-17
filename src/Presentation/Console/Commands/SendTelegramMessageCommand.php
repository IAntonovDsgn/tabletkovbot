<?php

declare(strict_types=1);

namespace App\Presentation\Console\Commands;

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Domain\Entities\Message\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SendTelegramMessageCommand extends Command
{
    public function __construct(
        private readonly MessageServiceInterface $messageService,
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
        $chatIdArg = $input->getArgument('chat_id');
        if (!is_string($chatIdArg) || !is_numeric($chatIdArg)) {
            $output->writeln('<error>chat_id must be numeric</error>');
            return self::FAILURE;
        }

        $message = $input->getArgument('message');
        if (!is_string($message)) {
            $output->writeln('<error>message must be a string</error>');
            return self::FAILURE;
        }

        try {
            $this->messageService->sendMessage(new Message((int) $chatIdArg, $message, []));
            return self::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln("<error>Failed to send message: " . $e->getMessage() . "</error>");
            return self::FAILURE;
        }
    }
}