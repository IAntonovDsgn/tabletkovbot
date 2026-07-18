<?php

namespace App\Infrastructure\Services\LogService;

use App\Domain\Services\LogService\LogServiceInterface;
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
final readonly class LogServiceService implements LogServiceInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function addRecord(string $name, array $arguments): void
    {
        $this->logger->$name(...$arguments);
    }
}
