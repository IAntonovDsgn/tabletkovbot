<?php

use Monolog\Level;
use Psr\Log\LoggerInterface;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;

return [
    'log.path' =>  __DIR__ . '/../storage/logs/app.log',
    'log.channel' => 'app',

    LoggerInterface::class => function (ContainerInterface $c) {
        $channel = $c->get('log.channel');
        $path = $c->get('log.path');

        $monolog = new MonologLogger($channel);
        $handler = new StreamHandler($path, Level::Debug);
        $monolog->pushHandler($handler);
        return $monolog;
    },

    'log' => \DI\get(LoggerInterface::class),
];
