<?php

namespace App\config;

use App\Domain\Telegram\TelegramFacadeInterface;
use App\Infrastructure\Services\Logger\LoggerFacade;
use App\Infrastructure\Services\Telegram\TelegramFacade;
use Psr\Log\LoggerInterface;

return [
    LoggerInterface::class => function () {
        return LoggerFacade::getLogger();
    },

    TelegramFacadeInterface::class => function () {
        return new TelegramFacade();
    },

    'telegram.config' => function () {
        return require __DIR__ . '/telegram.php';
    },

];
