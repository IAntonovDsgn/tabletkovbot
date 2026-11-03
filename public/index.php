<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Http\Router;
use DI\Container;
use Psr\Log\LoggerInterface;

$container = null;

try {
    /* @var Container $container */
    $container = require __DIR__ . '/../bootstrap/bootstrap.php';
    new Router($container)();
} catch (Throwable $e) {
    http_response_code(204);

    try {
        if ($container instanceof Container) {
            $logger = $container->get(LoggerInterface::class);

            if ($logger instanceof LoggerInterface) {
                $logger->error($e);
            } else {
                error_log((string) $e);
            }
        } else {
            error_log((string) $e);
        }
    } catch (Throwable) {
        error_log((string) $e);
    }
}
