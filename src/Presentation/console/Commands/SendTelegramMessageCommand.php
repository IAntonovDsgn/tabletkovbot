<?php

namespace App\Presentation\console\Commands;

use App\Services\TelegramService;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Command\Command;

class SendTelegramMessageCommand extends Command
{
    public function handle(TelegramService $telegramService): void
    {
        $message = $this->argument('message');
        $userId = $this->argument('user_id') ?? $this->getDefaultUserId();

        if (!is_string($userId) || !is_string($message)) {
            $this->error('User ID and Message ID and must be a string');
            return;
        }

        $parseMode = is_string($this->option('parse_mode'))
            ? $this->option('parse_mode')
            : null;

        $replyToMessageId = is_int($this->option('reply_to_message_id'))
            ? $this->option('reply_to_message_id')
            : null;

        try {
            $response = $telegramService->sendMessage($userId, $message, $replyToMessageId, $parseMode);
            $this->info(print_r($response, true));
        } catch (Exception $e) {
            $this->error('Failed to send message: ' . $e->getMessage());
        }
    }

    private function getDefaultUserId(): string
    {
        return is_string(Config::get('services.telegram.default_user_id'))
            ? Config::get('services.telegram.default_user_id')
            : '';
    }
}
