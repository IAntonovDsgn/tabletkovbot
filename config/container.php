<?php

namespace App\config;

use App\Domain\TelegramMessage\TelegramMessageFacadeInterface;
use App\Infrastructure\Services\Logger\LoggerFacade;
use App\Infrastructure\Services\TelegramMessage\TelegramTelegramMessageFacade;
use Psr\Log\LoggerInterface;

return [
    LoggerInterface::class => function () {
        return LoggerFacade::getLogger();
    },

    TelegramMessageFacadeInterface::class => function () {
        return new TelegramTelegramMessageFacade();
    },

    'telegram.config' => function () {
        return require __DIR__ . '/telegram.php';
    },

];
