<?php

namespace App\Presentation\console\Commands;

use App\Infrastructure\Services\Telegram\TelegramFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SendTelegramMessageCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('app:tg-bot-send-message');
        $this->addArgument('message', InputArgument::REQUIRED);
        $this->addArgument('chat_id', InputArgument::REQUIRED);
        $this->addArgument('reply_to_message_id', InputArgument::OPTIONAL);
        $this->addArgument('parse_mode', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message = $input->getArgument('message');
        $chatId = $input->getArgument('chat_id');

        if (!is_string($chatId) || !is_string($message)) {
            $output->writeln("<error>Chat ID and Message ID and must be a string</error>");
            return self::FAILURE;
        }

        $parseMode = is_string($input->hasArgument('parse_mode'))
            ? $input->getArgument('parse_mode')
            : null;

        $replyToMessageId = is_int($input->hasArgument('reply_to_message_id'))
            ? $input->getArgument('reply_to_message_id')
            : null;

        try {
            $response = TelegramFacade::sendMessage($chatId, $message, $replyToMessageId, $parseMode);
            $output->writeln(print_r($response, true));
            return self::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln("<error>Failed to send message: " . $e->getMessage() . "</error>");
            return self::FAILURE;
        }
    }
}
