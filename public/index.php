<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Http\Router;

use DI\Container;
use Illuminate\Support\Facades\Log;

try {
    /* @var Container $container */
    $container = require __DIR__ . '/../bootstrap/bootstrap.php';
    new Router($container)();
} catch (Exception $e) {
    Log::error($e);
    http_response_code(500);
}
