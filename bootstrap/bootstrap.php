<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Services\Logger\LoggerFacade;
use App\Infrastructure\Services\Telegram\TelegramFacade;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../docker/.env');

if (isset($_ENV['APP_TIMEZONE'])) {
    date_default_timezone_set($_ENV['APP_TIMEZONE']);
}

$loggerConfig = require __DIR__ . '/../config/logger.php';
LoggerFacade::init($loggerConfig);

$telegramConfig = require __DIR__ . '/../config/telegram.php';
TelegramFacade::init($telegramConfig);
