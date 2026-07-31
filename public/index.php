<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Http\Router;
use DI\Container;

/**
 * @var Container $container
 */
$container = require __DIR__ . '/../bootstrap/bootstrap.php';

new Router($container)();
