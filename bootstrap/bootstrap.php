<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Psr\Log\LoggerInterface;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

if (isset($_ENV['APP_TIMEZONE'])) {
    date_default_timezone_set($_ENV['APP_TIMEZONE']);
}

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->addDefinitions(require 'appServiceProvider.php');
$appContainer = $containerBuilder->build();

$logger = $appContainer->get(LoggerInterface::class);
$illuminateContainer = new IlluminateContainer();
$illuminateContainer->instance('log', $logger);

/** @var Application $illuminateContainer */
Facade::setFacadeApplication($illuminateContainer);

return $appContainer;
