<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Facade\ServiceContainer\ContainerService;
use DI\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../docker/.env');

if (isset($_ENV['APP_TIMEZONE'])) {
    date_default_timezone_set($_ENV['APP_TIMEZONE']);
}

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->addDefinitions(require __DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

ContainerService::set($container);
