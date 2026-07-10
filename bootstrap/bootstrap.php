<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\ServiceContainer\ServiceContainer;
use App\Infrastructure\Services\Logger\LoggerFacade;
use DI\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../docker/.env');

if (isset($_ENV['APP_TIMEZONE'])) {
    date_default_timezone_set($_ENV['APP_TIMEZONE']);
}

$loggerConfig = require __DIR__ . '/../config/logger.php';
LoggerFacade::init($loggerConfig);

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->addDefinitions(require __DIR__ . '/../config/container.php');

$container = $containerBuilder->build();

ServiceContainer::set($container);
