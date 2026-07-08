<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Infrastructure/Services/Logger/LoggerFacade.php';

use App\Infrastructure\Services\Logger\LoggerFacade;

$timezone = $_ENV['APP_TIMEZONE'];
date_default_timezone_set($timezone);

$config = require __DIR__ . '/../config/logger.php';
LoggerFacade::init($config);
