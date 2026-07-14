<?php

namespace App\config;

use App\Domain\Message\MessageFacadeInterface;
use App\Infrastructure\Services\Logger\LoggerFacade;
use App\Infrastructure\Services\Message\MessageFacade;
use Psr\Log\LoggerInterface;
use function DI\autowire;

return [
    LoggerInterface::class => function () {
        return LoggerFacade::getLogger();
    },

    MessageFacadeInterface::class => autowire(MessageFacade::class),

    'telegram.config' => function () {
        return require __DIR__ . '/telegram.php';
    },

];
