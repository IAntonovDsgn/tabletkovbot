<?php

namespace App\Infrastructure\Services\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggerFacade
{
    private static ?LoggerInterface $logger = null;

    public static function init(array $config): void
    {
        $channel  = $config['channel'] ?? 'app';
        $handlers = $config['handlers'] ?? [];

        $logger = new Logger($channel);

        foreach ($handlers as $handlerConfig) {
            $handler = new StreamHandler($handlerConfig['path'], $handlerConfig['level']);
            $logger->pushHandler($handler);
        }

        self::$logger = $logger;
    }

    public static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            throw new \RuntimeException('Logger has not been initialized. Call LoggerFacade::initFromConfig() first.');
        }
        return self::$logger;
    }

    public static function __callStatic(string $name, array $arguments): void
    {
        $logger = self::getLogger();
        if (method_exists($logger, $name)) {
            $logger->$name(...$arguments);
            return;
        }
        if ($name === 'log') {
            $logger->log(...$arguments);
            return;
        }
        throw new \BadMethodCallException("Method LoggerFacade::$name does not exist.");
    }
}
