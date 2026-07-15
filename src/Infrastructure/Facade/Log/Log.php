<?php

namespace App\Infrastructure\Facade\Log;

use App\Infrastructure\Facade\ServiceContainer\ServiceContainer;
use Psr\Log\LoggerInterface;

/**
 * @method static void emergency(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static void log(string $level, string $message, array $context = [])
 */
class Log
{
    private static ?LoggerInterface $logger = null;

    private static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            self::$logger = ServiceContainer::get(LoggerInterface::class);
        }
        return self::$logger;
    }

    public static function __callStatic(string $name, array $arguments): void
    {
        self::getLogger()->$name(...$arguments);
    }
}
