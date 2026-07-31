<?php

use App\Application\Services\MessageService\MessageServiceInterface;
use App\Infrastructure\Services\TelegramMessageService\TelegramMessageService;
use Psr\Container\ContainerInterface;
use Telegram\Bot\Api;
use function DI\autowire;
use function DI\get;

return [
    'telegram.config' => [
        'token' => $_ENV['TG_BOT_TOKEN'],
        'base_url' => $_ENV['TG_BOT_BASE_URL'],
        ],

    MessageServiceInterface::class => get(TelegramMessageService::class),

    TelegramMessageService::class => autowire()
        ->constructorParameter('telegramConfig', get('telegram.config')),

    Api::class => function (ContainerInterface $c) {
        $config = $c->get('telegram.config');
        return new Api($config['token']);
    },
];
